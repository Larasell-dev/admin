<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Larasell\Catalog\Manufacturers\Models\Manufacturer;
use Larasell\Larasell\Catalog\Manufacturers\PostalAddress;
use Larasell\Larasell\Catalog\Manufacturers\ResponsiblePerson;
use Larasell\Larasell\Catalog\Models\Product;
use Larasell\Larasell\Enums\Country;
use Larasell\Larasell\Price;

beforeEach(function () {
    $this->actingAs(Admin::create(['name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'password']), 'larasell-admin');
});

/** A US manufacturer, outside the EU, with its responsible person in Berlin. */
function acmeInc(): Manufacturer
{
    return Manufacturer::create([
        'name' => 'Acme Inc.',
        'address' => new PostalAddress('1 Main Street', '12345', 'Springfield', Country::US),
        'email' => 'legal@acme.test',
        'responsible_person' => new ResponsiblePerson('Acme GmbH', new PostalAddress('Hauptstraße 1', '10115', 'Berlin', Country::DE), 'eu@acme.test'),
    ]);
}

/** @return array<string, mixed> */
function manufacturerForm(array $overrides = []): array
{
    return array_replace_recursive([
        'name' => 'Acme Inc.',
        'email' => 'legal@acme.test',
        'url' => 'https://acme.test',
        'address' => ['street' => "1 Main Street\nSuite 200", 'postcode' => '12345', 'city' => 'Springfield', 'state' => 'IL', 'country' => 'US'],
        'responsible_person' => [
            'name' => 'Acme GmbH',
            'email' => 'eu@acme.test',
            'address' => ['street' => 'Hauptstraße 1', 'postcode' => '10115', 'city' => 'Berlin', 'state' => '', 'country' => 'DE'],
        ],
    ], $overrides);
}

it('lists manufacturers with their country and how many products they make', function () {
    // Arrange
    $acme = acmeInc();
    Product::create(['name' => 'Hammer', 'price' => Price::of(1000), 'manufacturer_id' => $acme->id]);

    // Act & Assert
    $this->get(route('larasell.admin.manufacturers.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('table.rows.0.name', 'Acme Inc.')
            ->where('table.rows.0.address', 'United States')
            ->where('table.rows.0.products_count', '1'));
});

it('creates a manufacturer with its street lines and EU responsible person', function () {
    // Act
    $response = $this->post(route('larasell.admin.manufacturers.store'), manufacturerForm());

    // Assert
    $acme = Manufacturer::query()->sole();
    $response->assertRedirect(route('larasell.admin.manufacturers.show', $acme));
    expect($acme->address->street)->toBe(['1 Main Street', 'Suite 200'])
        ->and($acme->address->state)->toBe('IL')
        ->and($acme->url)->toBe('https://acme.test')
        ->and($acme->responsible_person?->name)->toBe('Acme GmbH')
        ->and($acme->responsible_person?->address->country)->toBe(Country::DE)
        ->and($acme->responsible_person?->address->state)->toBeNull();
});

it('saves no responsible person while its name is empty', function () {
    // Arrange
    $acme = acmeInc();

    // Act
    $this->put(route('larasell.admin.manufacturers.update', $acme), manufacturerForm([
        'email' => '',
        'responsible_person' => ['name' => '', 'email' => '', 'address' => ['street' => '', 'postcode' => '', 'city' => '', 'country' => '']],
    ]))->assertSessionHasNoErrors();

    // Assert
    $acme->refresh();
    expect($acme->responsible_person)->toBeNull()
        ->and($acme->email)->toBeNull();
});

it('needs the address of a responsible person with a name, and of the manufacturer always', function () {
    // Act & Assert
    $this->post(route('larasell.admin.manufacturers.store'), manufacturerForm([
        'address' => ['city' => ''],
        'responsible_person' => ['address' => ['street' => '', 'country' => '']],
    ]))->assertSessionHasErrors(['address.city', 'responsible_person.address.street', 'responsible_person.address.country']);
    expect(Manufacturer::query()->exists())->toBeFalse();
});

it('shows the manufacturer as the form edits it', function () {
    // Arrange
    $acme = acmeInc();

    // Act & Assert
    $this->get(route('larasell.admin.manufacturers.show', $acme))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Manufacturer/Pages/Show')
            ->where('manufacturer.address.country', 'US')
            ->where('manufacturer.inEu', false)
            ->where('manufacturer.responsiblePersonName', 'Acme GmbH')
            ->where('manufacturer.responsiblePersonAddress.city', 'Berlin'));
});

it('deletes a manufacturer once its name is typed, and refuses one products use', function (bool $used, string $error) {
    // Arrange
    $acme = acmeInc();
    if ($used) {
        Product::create(['name' => 'Hammer', 'price' => Price::of(1000), 'manufacturer_id' => $acme->id]);
    }

    // Act
    $response = $this->delete(route('larasell.admin.manufacturers.destroy', $acme), ['confirmation' => 'Acme Inc.']);

    // Assert
    $used ? $response->assertSessionHasErrors(['manufacturer' => $error]) : $response->assertRedirect(route('larasell.admin.manufacturers.index'));
    expect(Manufacturer::query()->exists())->toBe($used);
})->with([
    'unused' => [false, ''],
    'used by a product' => [true, 'Used by 1 product. Give it another manufacturer first.'],
]);

it('finds manufacturers by email', function () {
    // Arrange
    acmeInc();

    // Act & Assert
    $this->getJson(route('larasell.admin.search.manufacturers', ['q' => 'legal@']))
        ->assertOk()
        ->assertJsonPath('results.0.label', 'Acme Inc.')
        ->assertJsonPath('results.0.match.value', 'legal@acme.test');
});

it('validates a manufacturer added on the product form without saving it', function () {
    // Act & Assert
    $this->postJson(route('larasell.admin.manufacturers.validate'), ['name' => 'Acme Inc.', 'address' => ['street' => '1 Main St']])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['address.city', 'address.country'])
        ->assertJsonMissingValidationErrors(['name']);
    expect(Manufacturer::query()->exists())->toBeFalse();
});
