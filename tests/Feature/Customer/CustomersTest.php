<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Larasell\Admin\Customer\Tables\Customers;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Larasell\Customers\Actions\CompanyProfileData;
use Larasell\Larasell\Customers\Actions\CreateCustomer;
use Larasell\Larasell\Customers\Actions\CreateCustomerPayload;
use Larasell\Larasell\Customers\Actions\PersonProfileData;
use Larasell\Larasell\Customers\Enums\CustomerSource;
use Larasell\Larasell\Customers\Enums\CustomerStatus;
use Larasell\Larasell\Customers\Enums\CustomerType;
use Larasell\Larasell\Customers\Models\Customer;
use Larasell\Larasell\EmailAddress;
use Larasell\Larasell\Enums\Country;
use Larasell\Larasell\Enums\Locale;

beforeEach(function () {
    $this->actingAs(Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']), 'larasell-admin');
});

function adminCustomer(PersonProfileData|CompanyProfileData|null $profile = null): Customer
{
    return app(CreateCustomer::class)->handle(new CreateCustomerPayload(
        profile: $profile ?? new PersonProfileData(firstName: 'Erika', lastName: 'Musterfrau', email: EmailAddress::of('erika@example.com')),
        source: CustomerSource::Storefront,
    ));
}

function customerAuditEvents(): array
{
    return DB::table('larasell_audit_log')->orderBy('id')->pluck('event')->all();
}

it('lists customers with their number, name, type and status, newest first', function () {
    // Arrange
    adminCustomer();
    adminCustomer(new CompanyProfileData(legalName: 'Muster GmbH', countryCode: Country::DE));

    // Act & Assert
    $this->get(route('larasell.admin.customers.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Platform/Tables/Pages/TablePage')
            ->where('table.title', 'Customers')
            ->where('table.pagination.total', 2)
            ->where('table.rows.0.customer_number', 'C-000002')
            ->where('table.rows.0.name', 'Muster GmbH')
            ->where('table.rows.0.type', 'Company')
            ->where('table.rows.0.status', 'Active')
            ->where('table.rows.1.name', 'Erika Musterfrau')
            ->where('table.rows.1.email', 'erika@example.com'));
});

it('creates a company customer by hand and audits it', function () {
    // Act
    $response = $this->post(route('larasell.admin.customers.store'), [
        'type' => 'company',
        'legal_name' => 'Muster GmbH',
        'country_code' => 'DE',
        'vat_id' => 'de 123 456 789',
    ]);

    // Assert
    $customer = Customer::query()->sole();
    $response->assertRedirect(route('larasell.admin.customers.show', $customer));
    expect($customer->type)->toBe(CustomerType::Company)
        ->and($customer->source)->toBe(CustomerSource::Admin)
        ->and($customer->companyProfile?->vat_id?->value())->toBe('DE123456789')
        ->and(customerAuditEvents())->toBe(['customer.created']);
});

it('creates an individual customer and asks for the fields its type needs', function () {
    // Act & Assert
    $this->post(route('larasell.admin.customers.store'), ['type' => 'individual', 'first_name' => 'Erika'])
        ->assertSessionHasErrors(['last_name']);
    $this->post(route('larasell.admin.customers.store'), ['type' => 'company', 'country_code' => 'Germany'])
        ->assertSessionHasErrors(['legal_name', 'country_code']);
    $this->post(route('larasell.admin.customers.store'), ['type' => 'individual', 'first_name' => 'Erika', 'last_name' => 'Musterfrau', 'email' => 'erika@example.com'])
        ->assertRedirect();

    expect(Customer::query()->sole()->personProfile?->email?->value())->toBe('erika@example.com');
});

it('shows a customer with everything the edit page needs', function () {
    // Arrange
    $customer = adminCustomer(new CompanyProfileData(legalName: 'Muster GmbH', countryCode: Country::DE));

    // Act & Assert
    $this->get(route('larasell.admin.customers.show', $customer))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Customer/Pages/Edit')
            ->where('customer.name', 'Muster GmbH')
            ->where('customer.type', 'company')
            ->where('customer.status', 'active')
            ->where('customer.company.legalName', 'Muster GmbH')
            ->where('customer.person', null)
            ->where('updateUrl', route('larasell.admin.customers.update', $customer))
            ->has('addresses', 0)
            ->has('contacts', 0)
            ->has('accounts', 0)
            ->has('groups', 0)
            ->has('availableGroups'));
});

it('updates a customer and audits it', function () {
    // Arrange
    $customer = adminCustomer();

    // Act
    $this->put(route('larasell.admin.customers.update', $customer), ['first_name' => 'Erika', 'last_name' => 'Beispiel', 'preferred_locale' => 'de-DE'])
        ->assertRedirect(route('larasell.admin.customers.show', $customer));

    // Assert
    $customer = $customer->fresh();
    expect($customer?->personProfile?->last_name)->toBe('Beispiel')
        ->and($customer?->preferred_locale)->toBe(Locale::GERMAN_GERMANY)
        ->and(customerAuditEvents())->toBe(['customer.created', 'customer.updated']);
});

it('rejects a preferred language that is no locale', function () {
    // Arrange
    $customer = adminCustomer();

    // Act & Assert
    $this->put(route('larasell.admin.customers.update', $customer), ['first_name' => 'Erika', 'last_name' => 'Beispiel', 'preferred_locale' => 'german'])
        ->assertSessionHasErrors('preferred_locale');
});

it('rejects a phone number with letters and a website that is no web address', function () {
    // Arrange
    $customer = adminCustomer();

    // Act & Assert
    $this->put(route('larasell.admin.customers.update', $customer), ['first_name' => 'Erika', 'last_name' => 'Beispiel', 'phone' => 'call me'])
        ->assertSessionHasErrors('phone');
});

it('rejects a VAT ID in no EU format', function () {
    // Arrange
    $customer = adminCustomer(new CompanyProfileData(legalName: 'Muster GmbH', countryCode: Country::DE));

    // Act & Assert
    $this->put(route('larasell.admin.customers.update', $customer), ['legal_name' => 'Muster GmbH', 'country_code' => 'DE', 'vat_id' => 'DE12345'])
        ->assertSessionHasErrors(['vat_id' => __('Enter a VAT ID such as DE123456789.')]);
});

it('stores a website without a scheme with https', function () {
    // Arrange
    $customer = adminCustomer(new CompanyProfileData(legalName: 'Muster GmbH', countryCode: Country::DE));

    // Act
    $this->put(route('larasell.admin.customers.update', $customer), ['legal_name' => 'Muster GmbH', 'country_code' => 'DE', 'website' => 'muster.de/shop', 'phone' => '+49 (0)30 123'])
        ->assertSessionHasNoErrors();

    // Assert
    expect($customer->fresh()?->companyProfile?->website?->value())->toBe('https://muster.de/shop');
    $this->put(route('larasell.admin.customers.update', $customer), ['legal_name' => 'Muster GmbH', 'country_code' => 'DE', 'website' => 'ftp://muster.de'])
        ->assertSessionHasErrors('website');
});

it('blocks, archives and reactivates a customer and audits each change', function () {
    // Arrange
    $customer = adminCustomer();

    // Act
    $this->post(route('larasell.admin.customers.status', $customer), ['status' => 'blocked'])->assertRedirect();
    $this->post(route('larasell.admin.customers.status', $customer), ['status' => 'archived'])->assertRedirect();
    $this->post(route('larasell.admin.customers.status', $customer), ['status' => 'active'])->assertRedirect();

    // Assert
    expect($customer->fresh()?->status)->toBe(CustomerStatus::Active)
        ->and(customerAuditEvents())->toBe(['customer.created', 'customer.status_changed', 'customer.status_changed', 'customer.status_changed']);
    $this->post(route('larasell.admin.customers.status', $customer), ['status' => 'deleted'])->assertSessionHasErrors('status');
});

it('finds customers by number, name and email in the command menu', function () {
    // Arrange
    $erika = adminCustomer();
    adminCustomer(new CompanyProfileData(legalName: 'Muster GmbH', countryCode: Country::DE));

    // Act & Assert
    $this->getJson(route('larasell.admin.search.customers', ['q' => 'erika@']))
        ->assertOk()
        ->assertJsonCount(1, 'results')
        ->assertJsonPath('results.0.label', 'Erika Musterfrau')
        ->assertJsonPath('results.0.url', route('larasell.admin.customers.show', $erika))
        ->assertJsonPath('results.0.match.value', 'erika@example.com');
    $this->getJson(route('larasell.admin.search.customers', ['q' => 'Muster GmbH']))
        ->assertJsonCount(1, 'results');
    $this->getJson(route('larasell.admin.search.customers', ['q' => 'C-000001']))
        ->assertJsonPath('results.0.label', 'Erika Musterfrau');
});

it('is not shown to guests', function () {
    // Arrange
    auth('larasell-admin')->logout();

    // Act & Assert
    $this->get(route('larasell.admin.customers.index'))->assertRedirect(route('larasell.admin.login'));
});

it('blocks, unblocks, deactivates and reactivates customers from the table, audited', function () {
    // Arrange
    $customer = adminCustomer();
    $table = Customers::make();
    $run = fn (string $action) => $this->post($table->actionUrl($table->action($action), $customer->fresh()));

    // Act & Assert
    $this->get(route('larasell.admin.customers.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('table.rows.0._actions', fn ($actions) => collect($actions)->pluck('label')->all() === ['Block', 'Deactivate']));
    $run('block')->assertRedirect();
    expect($customer->fresh()?->status)->toBe(CustomerStatus::Blocked);
    $this->get(route('larasell.admin.customers.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('table.rows.0._actions', fn ($actions) => collect($actions)->pluck('label')->all() === ['Unblock', 'Deactivate']));
    $run('block')->assertNotFound();
    $run('unblock')->assertRedirect();
    $run('deactivate')->assertRedirect();
    expect($customer->fresh()?->status)->toBe(CustomerStatus::Archived);
    $run('reactivate')->assertRedirect();
    expect($customer->fresh()?->status)->toBe(CustomerStatus::Active)
        ->and(customerAuditEvents())->toBe(['customer.created', ...array_fill(0, 4, 'customer.status_changed')]);
});
