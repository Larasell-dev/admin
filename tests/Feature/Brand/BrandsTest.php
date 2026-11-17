<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Larasell\Catalog\Brands\Models\Brand;
use Larasell\Larasell\Catalog\Models\Product;
use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\Price;

beforeEach(function () {
    config(['larasell.locale.supported' => [Locale::ENGLISH, Locale::GERMAN], 'larasell.locale.default' => Locale::ENGLISH]);

    $this->actingAs(Admin::create(['name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'password']), 'larasell-admin');
});

function acme(): Brand
{
    return Brand::create(['slug' => 'acme', 'name' => ['en' => 'Acme', 'de' => 'Akme'], 'description' => ['en' => 'Tools', 'de' => 'Werkzeug']]);
}

it('lists brands with how many products they have', function () {
    // Arrange
    $acme = acme();
    Product::create(['name' => 'Hammer', 'price' => Price::of(1000), 'brand_id' => $acme->id]);

    // Act & Assert
    $this->get(route('larasell.admin.brands.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Platform/Tables/Pages/TablePage')
            ->where('table.rows.0.name', 'Acme')
            ->where('table.rows.0.slug', 'acme')
            ->where('table.rows.0.products_count', '1'));
});

it('creates a brand in the content locale, audited, and opens its page', function () {
    // Arrange
    $this->put(route('larasell.admin.content-locale.update'), ['locale' => 'de']);

    // Act
    $response = $this->post(route('larasell.admin.brands.store'), ['name' => 'Akme', 'slug' => 'acme', 'description' => 'Werkzeug']);

    // Assert
    $brand = Brand::query()->sole();
    $response->assertRedirect(route('larasell.admin.brands.show', $brand));
    expect($brand->name->all())->toBe(['de' => 'Akme'])
        ->and($brand->description?->all())->toBe(['de' => 'Werkzeug'])
        ->and(DB::table('larasell_audit_log')->where('event', 'brand.created')->exists())->toBeTrue();
});

it('changes the texts in the content locale only and keeps the other translations', function () {
    // Arrange
    $acme = acme();
    $this->put(route('larasell.admin.content-locale.update'), ['locale' => 'de']);

    // Act
    $this->put(route('larasell.admin.brands.update', $acme), ['name' => 'Akme GmbH', 'slug' => 'acme-tools', 'description' => ''])
        ->assertSessionHasNoErrors();

    // Assert
    $acme->refresh();
    expect($acme->name->all())->toBe(['en' => 'Acme', 'de' => 'Akme GmbH'])
        ->and($acme->description?->all())->toBe(['en' => 'Tools'])
        ->and($acme->slug)->toBe('acme-tools');
});

it('shows the brand in the content locale with how many products use it', function () {
    // Arrange
    $acme = acme();
    $this->put(route('larasell.admin.content-locale.update'), ['locale' => 'de']);

    // Act & Assert
    $this->get(route('larasell.admin.brands.show', $acme))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Brand/Pages/Show')
            ->where('brand.name', 'Akme')
            ->where('brand.description', 'Werkzeug')
            ->where('brand.products', 0)
            ->where('destroyUrl', route('larasell.admin.brands.destroy', $acme)));
});

it('rejects a slug another brand has', function () {
    // Arrange
    acme();

    // Act & Assert
    $this->post(route('larasell.admin.brands.store'), ['name' => 'Other', 'slug' => 'acme'])
        ->assertSessionHasErrors('slug');
});

it('deletes a brand once its slug is typed', function (string $typed, bool $deleted) {
    // Arrange
    $acme = acme();

    // Act
    $response = $this->delete(route('larasell.admin.brands.destroy', $acme), ['confirmation' => $typed]);

    // Assert
    $deleted ? $response->assertRedirect(route('larasell.admin.brands.index')) : $response->assertSessionHasErrors('confirmation');
    expect(Brand::query()->exists())->toBe(! $deleted);
})->with([
    'its slug' => ['acme', true],
    'its name' => ['Acme', false],
]);

it('refuses to delete a brand products use, saying how many', function () {
    // Arrange
    $acme = acme();
    Product::create(['name' => 'Hammer', 'price' => Price::of(1000), 'brand_id' => $acme->id]);
    Product::create(['name' => 'Saw', 'price' => Price::of(1000), 'brand_id' => $acme->id]);

    // Act & Assert
    $this->delete(route('larasell.admin.brands.destroy', $acme), ['confirmation' => 'acme'])
        ->assertSessionHasErrors(['brand' => 'Used by 2 products. Give them another brand first.']);
    expect(Brand::query()->exists())->toBeTrue();
});

it('finds brands by a name in any language, or by slug', function () {
    // Arrange
    acme();

    // Act & Assert
    $this->getJson(route('larasell.admin.search.brands', ['q' => 'akm']))
        ->assertOk()
        ->assertJsonPath('results.0.label', 'Acme');
});

it('validates a brand added on the product form without saving it', function () {
    // Arrange
    acme();

    // Act & Assert
    $this->postJson(route('larasell.admin.brands.validate'), ['name' => 'Acme', 'slug' => 'acme'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['slug'])
        ->assertJsonMissingValidationErrors(['name']);
    $this->postJson(route('larasell.admin.brands.validate'), ['name' => 'Bolt', 'slug' => 'bolt'])->assertNoContent();
    expect(Brand::query()->count())->toBe(1);
});

it('lets a brand edited on the product form keep its own slug', function () {
    // Arrange
    $acme = acme();

    // Act & Assert
    $this->postJson(route('larasell.admin.brands.validate'), ['brand' => $acme->id, 'name' => 'Acme', 'slug' => 'acme'])->assertNoContent();
});
