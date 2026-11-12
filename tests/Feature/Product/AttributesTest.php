<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Larasell\Catalog\AttributeCardinality;
use Larasell\Larasell\Catalog\AttributeScope;
use Larasell\Larasell\Catalog\Models\Product;
use Larasell\Larasell\Catalog\Models\ProductAttribute;
use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\Enums\ProductAttributeType;
use Larasell\Larasell\Price;

beforeEach(function () {
    $this->actingAs(Admin::create(['name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'password']), 'larasell-admin');
});

/** A material attribute with cotton and wool, cotton on one product. */
function material(): array
{
    $attribute = ProductAttribute::query()->create(['slug' => 'material', 'name' => ['en' => 'Material'], 'type' => ProductAttributeType::Text, 'scope' => AttributeScope::Inherited]);
    $cotton = $attribute->values()->create(['slug' => 'cotton', 'name' => ['en' => 'Cotton'], 'value' => 'Cotton', 'position' => 1]);
    $wool = $attribute->values()->create(['slug' => 'wool', 'name' => ['en' => 'Wool'], 'value' => 'Wool', 'position' => 2]);
    $product = Product::create(['name' => 'Shirt', 'price' => Price::of(1000)]);
    $product->attributeValues()->attach($cotton->id);

    return [$attribute, $cotton, $wool, $product];
}

it('lists attributes with how many values, products and variants they have', function () {
    // Arrange
    material();

    // Act & Assert
    $this->get(route('larasell.admin.attributes.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Platform/Tables/Pages/TablePage')
            ->where('table.rows.0.name', 'Material')
            ->where('table.rows.0.values_count', '2')
            ->where('table.rows.0.products_count', '1')
            ->where('table.rows.0.variants_count', '0'));
});

it('creates an attribute with its values and opens its page', function () {
    // Act
    $response = $this->post(route('larasell.admin.attributes.store'), [
        'name' => 'Care', 'slug' => 'care', 'type' => 'text', 'scope' => 'variant', 'multiple' => '1',
        'values' => [['name' => 'Hand wash', 'slug' => 'hand-wash'], ['name' => 'Dry clean', 'slug' => 'dry-clean']],
    ]);

    // Assert
    $attribute = ProductAttribute::query()->where('slug', 'care')->sole();
    $response->assertRedirect(route('larasell.admin.attributes.show', $attribute));
    expect($attribute->scope)->toBe(AttributeScope::Variant)
        ->and($attribute->cardinality)->toBe(AttributeCardinality::Multiple)
        ->and($attribute->values()->orderBy('position')->pluck('slug')->all())->toBe(['hand-wash', 'dry-clean'])
        ->and(DB::table('larasell_audit_log')->where('event', 'product_attribute.created')->exists())->toBeTrue();
});

it('saves the attribute with its values in one request, keeping other languages', function () {
    // Arrange
    [$attribute, $cotton, $wool, $product] = material();
    $cotton->update(['name' => ['en' => 'Cotton', 'de-DE' => 'Baumwolle']]);

    // Act
    $this->put(route('larasell.admin.attributes.update', $attribute), [
        'name' => 'Fabric', 'slug' => 'material', 'scope' => 'inherited', 'multiple' => '0',
        'values' => [['name' => 'Linen', 'slug' => 'linen'], ['id' => $cotton->id, 'name' => 'Organic cotton', 'slug' => 'cotton']],
    ])->assertSessionHasNoErrors();

    // Assert
    expect($attribute->values()->orderBy('position')->get()->map(fn ($value) => $value->slug)->all())->toBe(['linen', 'cotton'])
        ->and($cotton->fresh()->name->all())->toBe(['en' => 'Organic cotton', 'de-DE' => 'Baumwolle'])
        ->and($wool->fresh())->toBeNull()
        ->and($product->attributeValues()->pluck('slug')->all())->toBe(['cotton']);
});

it('refuses another attribute\'s value id and duplicate slugs', function () {
    // Arrange
    [$attribute] = material();
    $care = ProductAttribute::query()->create(['slug' => 'care', 'name' => ['en' => 'Care'], 'type' => ProductAttributeType::Text]);
    $wash = $care->values()->create(['slug' => 'wash', 'name' => ['en' => 'Wash'], 'value' => 'Wash']);

    // Act & Assert
    $this->put(route('larasell.admin.attributes.update', $attribute), [
        'name' => 'Material', 'slug' => 'material', 'scope' => 'inherited', 'multiple' => '0',
        'values' => [['id' => $wash->id, 'name' => 'Wash', 'slug' => 'wash'], ['name' => 'Silk', 'slug' => 'silk'], ['name' => 'Satin', 'slug' => 'silk']],
    ])->assertSessionHasErrors(['values.0.id', 'values.1.slug', 'values.2.slug']);
    expect($wash->fresh()->product_attribute_id)->toBe($care->id)
        ->and($attribute->values()->count())->toBe(2);
});

it('refuses to make an attribute variants-only while products have values of it', function () {
    // Arrange
    [$attribute, $cotton, $wool] = material();

    // Act & Assert
    $this->put(route('larasell.admin.attributes.update', $attribute), [
        'name' => 'Material', 'slug' => 'material', 'scope' => 'variant', 'multiple' => '0',
        'values' => [['id' => $cotton->id, 'name' => 'Cotton', 'slug' => 'cotton'], ['id' => $wool->id, 'name' => 'Wool', 'slug' => 'wool']],
    ])->assertSessionHasErrors('scope');
    expect($attribute->fresh()->scope)->toBe(AttributeScope::Inherited);
});

it('leaves names empty in a language to fall back to another, but not where no other has one', function () {
    // Arrange
    config(['larasell.locale.supported' => [Locale::ENGLISH, Locale::GERMAN], 'larasell.locale.default' => Locale::ENGLISH]);
    [$attribute, $cotton, $wool] = material();
    $cotton->update(['name' => ['en' => 'Cotton', 'de' => 'Baumwolle']]);
    $this->put(route('larasell.admin.content-locale.update'), ['locale' => 'de'])->assertSessionHasNoErrors();
    $values = fn (string $woolName) => [
        ['id' => $cotton->id, 'name' => '', 'slug' => 'cotton'],
        ['id' => $wool->id, 'name' => $woolName, 'slug' => 'wool'],
        ['name' => '', 'slug' => 'silk'],
    ];

    // Act & Assert
    $this->put(route('larasell.admin.attributes.update', $attribute), ['name' => '', 'slug' => 'material', 'scope' => 'inherited', 'multiple' => '0', 'values' => $values('Wolle')])
        // Silk is new, so it has no other language to fall back to.
        ->assertSessionHasErrors(['values.2.name'])
        ->assertSessionDoesntHaveErrors(['name', 'values.0.name']);
    $this->put(route('larasell.admin.attributes.update', $attribute), ['name' => '', 'slug' => 'material', 'scope' => 'inherited', 'multiple' => '0', 'values' => array_slice($values('Wolle'), 0, 2)])
        ->assertSessionHasNoErrors();
    expect($attribute->fresh()->name->all())->toBe(['en' => 'Material'])
        ->and($cotton->fresh()->name->all())->toBe(['en' => 'Cotton'])
        ->and($wool->fresh()->name->all())->toBe(['en' => 'Wool', 'de' => 'Wolle']);
});

it('creates a yes-or-no attribute with its yes and no, which keep their slugs', function () {
    // Arrange
    $fields = ['name' => 'Waterproof', 'slug' => 'waterproof', 'type' => 'boolean', 'scope' => 'product', 'multiple' => '0'];

    // Act
    $this->post(route('larasell.admin.attributes.store'), [...$fields, 'values' => [['name' => 'Yes', 'slug' => '__boolean_true'], ['name' => 'No', 'slug' => '__boolean_false']]])
        ->assertSessionHasNoErrors();
    $attribute = ProductAttribute::query()->where('slug', 'waterproof')->sole();
    [$yes, $no] = $attribute->values()->orderBy('position')->get()->all();

    // Assert
    expect($attribute->type)->toBe(ProductAttributeType::Boolean)
        ->and([$yes->value, $no->value])->toBe([true, false]);
    $this->put(route('larasell.admin.attributes.update', $attribute), [...$fields, 'values' => [
        ['id' => $yes->id, 'name' => 'Yes', 'slug' => '__boolean_true'], ['id' => $no->id, 'name' => 'No', 'slug' => '__boolean_false'], ['name' => 'Maybe', 'slug' => 'maybe'],
    ]])->assertSessionHasErrors(['values', 'values.2.slug']);
    $this->put(route('larasell.admin.attributes.update', $attribute), [...$fields, 'type' => 'text', 'values' => [
        ['id' => $yes->id, 'name' => 'Ja', 'slug' => '__boolean_true'], ['id' => $no->id, 'name' => 'Nein', 'slug' => '__boolean_false'],
    ]])->assertSessionHasNoErrors();
    expect($attribute->fresh()->type)->toBe(ProductAttributeType::Boolean)
        ->and($yes->fresh()->name->get('en'))->toBe('Ja');
});

it('takes only numbers as a number attribute\'s values, and keeps their numbers in step with renames', function () {
    // Arrange
    $attribute = ProductAttribute::query()->create(['slug' => 'weight', 'name' => ['en' => 'Weight'], 'type' => ProductAttributeType::Number]);
    $light = $attribute->values()->create(['slug' => 'light', 'name' => ['en' => '180'], 'value' => 180]);
    $fields = ['name' => 'Weight', 'slug' => 'weight', 'scope' => 'product', 'multiple' => '0'];

    // Act & Assert
    $this->put(route('larasell.admin.attributes.update', $attribute), [...$fields, 'values' => [['id' => $light->id, 'name' => 'heavy', 'slug' => 'light']]])
        ->assertSessionHasErrors('values.0.name');
    $this->put(route('larasell.admin.attributes.update', $attribute), [...$fields, 'values' => [['id' => $light->id, 'name' => '200', 'slug' => 'light']]])
        ->assertSessionHasNoErrors();
    expect($light->fresh()->value)->toBe(200);
});
