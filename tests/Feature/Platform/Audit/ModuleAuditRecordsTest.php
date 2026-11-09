<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Larasell\Admin\Platform\Audit\Actors\Actor;
use Larasell\Admin\Platform\Audit\Actors\UnknownActor;
use Larasell\Admin\Platform\Audit\Chain\AuditChain;
use Larasell\Admin\Platform\Audit\Enums\AuditSource;
use Larasell\Admin\Platform\Audit\Facades\Audit;
use Larasell\Admin\Platform\Audit\Subject;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Admin\Product\AuditRecords\ProductCreated;
use Larasell\Admin\Product\AuditRecords\ProductDeleted;
use Larasell\Admin\Product\AuditRecords\ProductUpdated as ProductUpdatedRecord;
use Larasell\Admin\Seo\AuditRecords\SeoUrlReleased as SeoUrlReleasedRecord;
use Larasell\Larasell\Catalog\Events\ProductUpdated;
use Larasell\Larasell\Catalog\Models\Product;
use Larasell\Larasell\Catalog\ProductId;
use Larasell\Larasell\Price;
use Larasell\Larasell\SalesChannels\SalesChannelId;
use Larasell\Larasell\Seo\Events\SeoUrlReleased;
use Larasell\Larasell\Seo\SeoUrlStatus;
use Larasell\Larasell\Storefront\Models\EntryPoint;

beforeEach(function () {
    config(['larasell.locale.supported' => ['en', 'de']]);
    $this->admin = Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);
});

/**
 * @return list<object>
 */
function moduleRecords(string $event): array
{
    return DB::table('larasell_audit_log')->where('event', $event)->orderBy('seq')->get()
        ->map(fn (object $record) => (object) [...(array) $record, 'properties' => json_decode($record->properties, true)])
        ->all();
}

it('audits creating, updating and deleting a product as the admin who did', function () {
    // Arrange
    Audit::fake();
    $this->actingAs($this->admin, 'larasell-admin');

    // Act
    $this->post(route('larasell.admin.products.store'), ['name' => 'Boots', 'status' => 'visible'])->assertRedirect();
    $boots = Product::query()->sole();
    $this->put(route('larasell.admin.products.update', $boots), ['name' => 'Boots', 'status' => 'hidden'])->assertRedirect();
    $delete = $this->get(route('larasell.admin.products.index'))->inertiaProps('table.rows.0._actions.0.url');
    $this->from(route('larasell.admin.products.index'))->post($delete)->assertRedirect();

    // Assert
    $byAdmin = fn (Actor|UnknownActor $actor) => $actor instanceof Admin && $actor->is($this->admin);

    Audit::assertRecorded(ProductCreated::class, fn (ProductCreated $record, Actor|UnknownActor $actor) => $byAdmin($actor)
        && $record->subject() == new Subject('product', $boots->id)
        && $record->properties() === ['name' => ['en' => 'Boots']]);
    Audit::assertRecorded(ProductUpdatedRecord::class, fn (ProductUpdatedRecord $record, Actor|UnknownActor $actor) => $byAdmin($actor)
        && in_array('status', $record->properties()['changed'], true));
    Audit::assertRecorded(ProductDeleted::class, fn (ProductDeleted $record, Actor|UnknownActor $actor) => $byAdmin($actor)
        && $record->properties() === ['name' => ['en' => 'Boots']]);
});

it('audits a product changed by a command as an unknown actor, instead of failing the change', function () {
    // Arrange
    Audit::fake();
    $boots = Product::create(['name' => 'Boots', 'price' => Price::of(1000)]);

    // Act
    DB::transaction(fn () => ProductUpdated::dispatch(new ProductId($boots->id), ['price']));

    // Assert
    Audit::assertRecorded(ProductUpdatedRecord::class, fn (ProductUpdatedRecord $record, Actor|UnknownActor $actor, AuditSource $source) => $actor instanceof UnknownActor
        && $source === AuditSource::Cli);
});

it('audits creating, updating and deleting a sales channel and its entry points', function () {
    // Arrange
    $this->actingAs($this->admin, 'larasell-admin');
    $input = ['key' => 'b2b', 'name' => 'Wholesale', 'type' => 'storefront', 'locales' => ['de', 'en'], 'currencies' => ['USD'], 'default_locale' => 'en', 'default_currency' => 'USD', 'active' => '1'];

    // Act
    $this->post(route('larasell.admin.sales-channels.store'), $input)->assertRedirect();
    $id = (int) DB::table('larasell_audit_log')->where('event', 'sales_channel.created')->value('subject_id');
    $this->put(route('larasell.admin.sales-channels.update', $id), [...$input, 'name' => 'B2B'])->assertRedirect();
    $this->post(route('larasell.admin.sales-channels.entry-points.store', $id), ['url' => 'https://b2b.example', 'locale' => 'en'])->assertSessionHasNoErrors();
    $entryPoint = EntryPoint::query()->where('sales_channel_id', $id)->sole();
    $this->delete(route('larasell.admin.sales-channels.entry-points.destroy', [$id, $entryPoint->id]), ['confirmation' => 'b2b.example'])->assertSessionHasNoErrors();
    $this->delete(route('larasell.admin.sales-channels.destroy', $id), ['confirmation' => 'B2B'])->assertSessionHasNoErrors();

    // Assert
    expect(moduleRecords('sales_channel.created')[0]->properties)->toBe(['key' => 'b2b', 'type' => 'storefront'])
        ->and(moduleRecords('sales_channel.updated')[0]->properties)->toBe(['changed' => ['name']])
        ->and(moduleRecords('sales_channel.entry_point_added')[0])->toMatchObject(['subject_type' => 'sales_channel', 'subject_id' => (string) $id, 'actor_id' => (string) $this->admin->id])
        ->and(moduleRecords('sales_channel.entry_point_added')[0]->properties)->toBe(['entry_point_id' => $entryPoint->id, 'locale' => 'en', 'default_currency' => null])
        ->and(moduleRecords('sales_channel.entry_point_deleted')[0]->properties)->toBe(['entry_point_id' => $entryPoint->id])
        ->and(moduleRecords('sales_channel.deleted')[0]->properties)->toBe(['key' => 'b2b', 'type' => 'storefront'])
        ->and(app(AuditChain::class)->verify()->passed)->toBeTrue();
});

it('audits an old URL being released', function () {
    // Arrange
    Audit::fake();
    Route::middleware('web')->post('urls/release', fn () => SeoUrlReleased::dispatch(new SalesChannelId(1), 'en', '/old-boots', 'product', 7, SeoUrlStatus::Historic));

    // Act
    $this->actingAs($this->admin, 'larasell-admin')->post('urls/release')->assertOk();

    // Assert
    Audit::assertRecorded(SeoUrlReleasedRecord::class, fn (SeoUrlReleasedRecord $record, Actor|UnknownActor $actor) => $actor instanceof Admin && $actor->is($this->admin)
        && $record->subject() == new Subject('product', 7)
        && $record->properties() === ['sales_channel_id' => 1, 'locale' => 'en', 'path' => '/old-boots', 'status' => 'historic']);
});
