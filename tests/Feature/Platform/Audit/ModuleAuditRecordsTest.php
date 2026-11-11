<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Larasell\Audit\Actors\Actor;
use Larasell\Larasell\Audit\Chain\AuditChain;
use Larasell\Larasell\Audit\Facades\Audit;
use Larasell\Larasell\Audit\Origin;
use Larasell\Larasell\Audit\Subject;
use Larasell\Larasell\Catalog\AuditRecords\ProductCreated;
use Larasell\Larasell\Catalog\AuditRecords\ProductDeleted;
use Larasell\Larasell\Catalog\AuditRecords\ProductUpdated as ProductUpdatedRecord;
use Larasell\Larasell\Catalog\Models\Product;
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
    $byAdmin = fn (Actor $actor) => $actor instanceof Admin && $actor->is($this->admin);

    Audit::assertRecorded(ProductCreated::class, fn (ProductCreated $record, Origin $origin) => $byAdmin($origin->actor)
        && $record->subject() == new Subject('product', $boots->id)
        && $record->properties() === ['name' => ['en' => 'Boots']]);
    Audit::assertRecorded(ProductUpdatedRecord::class, fn (ProductUpdatedRecord $record, Origin $origin) => $byAdmin($origin->actor)
        && in_array('status', $record->properties()['changed'], true));
    Audit::assertRecorded(ProductDeleted::class, fn (ProductDeleted $record, Origin $origin) => $byAdmin($origin->actor)
        && $record->properties() === ['name' => ['en' => 'Boots']]);
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
