<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Larasell\Catalog\Models\Product;
use Larasell\Larasell\Catalog\Options\Models\OptionDefinition;
use Larasell\Larasell\Price;

beforeEach(function () {
    $this->actingAs(Admin::create(['name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'password']), 'larasell-admin');
});

/** Colour with black, white and red; a shirt has a black and a white variant. */
function colourOption(): array
{
    $colour = OptionDefinition::create(['slug' => 'colour', 'name' => ['en' => 'Colour'], 'display' => 'swatch']);
    $black = $colour->values()->create(['slug' => 'black', 'name' => ['en' => 'Black'], 'swatch' => '#000000', 'position' => 1]);
    $white = $colour->values()->create(['slug' => 'white', 'name' => ['en' => 'White'], 'swatch' => '#ffffff', 'position' => 2]);
    $red = $colour->values()->create(['slug' => 'red', 'name' => ['en' => 'Red'], 'swatch' => '#ff0000', 'position' => 3]);
    $shirt = Product::create(['name' => 'Shirt', 'price' => Price::of(1000)]);
    $shirt->addOption($colour, [$black, $white]);
    $shirt->createCombinations([[$black], [$white]]);

    return [$colour, $black, $white, $red, $shirt];
}

it('lists options with their display, values, products and variants', function () {
    // Arrange
    colourOption();

    // Act & Assert
    $this->get(route('larasell.admin.options.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Platform/Tables/Pages/TablePage')
            ->where('table.rows.0.name', 'Colour')
            ->where('table.rows.0.display', 'Swatches')
            ->where('table.rows.0.values_count', '3')
            ->where('table.rows.0.products_count', '1')
            ->where('table.rows.0.variants_count', '2'));
});

it('creates an option with its display and swatches and opens its page', function () {
    // Act
    $response = $this->post(route('larasell.admin.options.store'), [
        'name' => 'Size', 'slug' => 'size', 'display' => 'buttons',
        'values' => [['name' => 'Small', 'slug' => 's'], ['name' => 'Large', 'slug' => 'l', 'swatch' => '#AABBCC']],
    ]);

    // Assert
    $size = OptionDefinition::query()->where('slug', 'size')->sole();
    $response->assertRedirect(route('larasell.admin.options.show', $size));
    expect($size->display)->toBe('buttons')
        ->and($size->values->map(fn ($value) => [$value->slug, $value->swatch])->all())->toBe([['s', null], ['l', '#aabbcc']])
        ->and(DB::table('larasell_audit_log')->where('event', 'option_definition.created')->exists())->toBeTrue()
        ->and(DB::table('larasell_audit_log')->where('event', 'option_definition_value.created')->count())->toBe(2);
});

it('shows an option with its values and how many variants use each', function () {
    // Arrange
    [$colour] = colourOption();

    // Act & Assert
    $this->get(route('larasell.admin.options.show', $colour))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Product/Pages/Options/Show')
            ->where('option.display', 'swatch')
            ->where('option.variants', 2)
            ->where('values.0.swatch', '#000000')
            ->where('values.0.variants', 1)
            ->where('values.2.variants', 0));
});

it('saves the option with its values in one request, keeping other languages', function () {
    // Arrange
    [$colour, $black, $white, $red] = colourOption();
    $black->update(['name' => ['en' => 'Black', 'de-DE' => 'Schwarz']]);

    // Act
    $this->put(route('larasell.admin.options.update', $colour), [
        'name' => 'Color', 'slug' => 'colour', 'display' => 'dropdown',
        'values' => [
            ['id' => $white->id, 'name' => 'White', 'slug' => 'white', 'swatch' => '#fefefe'],
            ['name' => 'Blue', 'slug' => 'blue', 'swatch' => '#0000ff'],
            ['id' => $black->id, 'name' => 'Jet black', 'slug' => 'black', 'swatch' => ''],
        ],
    ])->assertSessionHasNoErrors();

    // Assert
    expect($colour->fresh()->display)->toBe('dropdown')
        ->and($colour->values()->get()->map(fn ($value) => [$value->slug, $value->swatch])->all())
        ->toBe([['white', '#fefefe'], ['blue', '#0000ff'], ['black', null]])
        ->and($black->fresh()->name->all())->toBe(['en' => 'Jet black', 'de-DE' => 'Schwarz'])
        ->and($red->fresh())->toBeNull()
        ->and(DB::table('larasell_audit_log')->where('event', 'option_definition_value.deleted')->count())->toBe(1);
});

it('refuses to delete a value variants use, saying how many on how many products', function () {
    // Arrange
    [$colour, $black, $white, $red] = colourOption();

    // Act & Assert
    $this->put(route('larasell.admin.options.update', $colour), [
        'name' => 'Colour', 'slug' => 'colour', 'display' => 'swatch',
        'values' => [['id' => $black->id, 'name' => 'Black', 'slug' => 'black'], ['id' => $red->id, 'name' => 'Red', 'slug' => 'red']],
    ])->assertSessionHasErrors(['values' => 'White is used by 1 variant of 1 product. Delete those variants on the product first.']);
    expect($white->fresh())->not->toBeNull();
});

it('refuses invalid display, swatch, another option\'s value and duplicate slugs', function () {
    // Arrange
    [$colour] = colourOption();
    $size = OptionDefinition::create(['slug' => 'size', 'name' => ['en' => 'Size']]);
    $small = $size->values()->create(['slug' => 's', 'name' => ['en' => 'S']]);

    // Act & Assert
    $this->put(route('larasell.admin.options.update', $colour), [
        'name' => 'Colour', 'slug' => 'size', 'display' => 'radio',
        'values' => [['id' => $small->id, 'name' => 'S', 'slug' => 's'], ['name' => 'A', 'slug' => 'a', 'swatch' => 'red'], ['name' => 'B', 'slug' => 'a']],
    ])->assertSessionHasErrors(['slug', 'display', 'values.0.id', 'values.1.swatch', 'values.2.slug']);
    expect($small->fresh()->option_definition_id)->toBe($size->id);
});

it('deletes an option only while no variant uses it', function () {
    // Arrange
    [$colour] = colourOption();
    $size = OptionDefinition::create(['slug' => 'size', 'name' => ['en' => 'Size']]);

    // Act & Assert
    $this->delete(route('larasell.admin.options.destroy', $size))->assertSessionHasErrors('confirmation');
    $this->delete(route('larasell.admin.options.destroy', $colour), ['confirmation' => 'colour'])
        ->assertSessionHasErrors(['option' => 'Colour is used by 2 variants of 1 product. Delete those variants on the product first.']);
    expect($colour->fresh())->not->toBeNull();
    $this->delete(route('larasell.admin.options.destroy', $size), ['confirmation' => 'size'])->assertRedirect(route('larasell.admin.options.index'));
    expect($size->fresh())->toBeNull()
        ->and(DB::table('larasell_audit_log')->where('event', 'option_definition.deleted')->exists())->toBeTrue();
});
