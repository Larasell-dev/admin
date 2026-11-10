<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Larasell\Customers\Actions\AddCustomerContact;
use Larasell\Larasell\Customers\Actions\CompanyProfileData;
use Larasell\Larasell\Customers\Actions\CreateCustomer;
use Larasell\Larasell\Customers\Actions\CreateCustomerGroup;
use Larasell\Larasell\Customers\Actions\CreateCustomerPayload;
use Larasell\Larasell\Customers\Actions\CustomerContactPayload;
use Larasell\Larasell\Customers\Actions\CustomerGroupPayload;
use Larasell\Larasell\Customers\Actions\LinkCustomerAccount;
use Larasell\Larasell\Customers\Actions\PersonProfileData;
use Larasell\Larasell\Customers\CustomerContactId;
use Larasell\Larasell\Customers\CustomerGroupCode;
use Larasell\Larasell\Customers\CustomerId;
use Larasell\Larasell\Customers\Enums\CustomerSource;
use Larasell\Larasell\Customers\Models\Customer;
use Larasell\Larasell\Customers\Models\CustomerAddress;
use Larasell\Larasell\Enums\Country;
use Larasell\Larasell\Translatable;

/** The app's own user, which signs in to the storefront. */
class CustomerDetailsTestUser extends Authenticatable
{
    protected $table = 'customer_details_test_users';

    protected $guarded = [];
}

beforeEach(function () {
    Schema::create('customer_details_test_users', function (Blueprint $table) {
        $table->id();
        $table->string('email');
        $table->timestamps();
    });
    $this->actingAs(Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']), 'larasell-admin');
});

function detailsCompany(): Customer
{
    return app(CreateCustomer::class)->handle(new CreateCustomerPayload(
        profile: new CompanyProfileData(legalName: 'Muster GmbH', countryCode: Country::DE),
        source: CustomerSource::Admin,
    ));
}

/** @return array<string, mixed> */
function addressInput(array $overrides = []): array
{
    return [
        'first_name' => 'Max',
        'last_name' => 'Mustermann',
        'company' => 'Muster GmbH',
        'street' => ['Hauptstraße 1'],
        'postcode' => '10115',
        'city' => 'Berlin',
        'country_code' => 'DE',
        ...$overrides,
    ];
}

it('adds, edits, makes default and deletes addresses', function () {
    // Arrange
    $customer = detailsCompany();

    // Act
    $this->post(route('larasell.admin.customers.addresses.store', $customer), addressInput())->assertRedirect(route('larasell.admin.customers.show', $customer));
    $this->post(route('larasell.admin.customers.addresses.store', $customer), addressInput(['city' => 'Hamburg']))->assertRedirect();
    $hamburg = CustomerAddress::query()->where('city', 'Hamburg')->sole();
    $this->put(route('larasell.admin.customers.addresses.update', [$customer, $hamburg]), addressInput(['city' => 'München']))->assertRedirect();
    $this->post(route('larasell.admin.customers.addresses.default', [$customer, $hamburg]), ['use' => 'shipping'])->assertRedirect();

    // Assert
    $this->get(route('larasell.admin.customers.show', $customer))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('addresses', 2)
            ->where('addresses.0.city', 'Berlin')
            ->where('addresses.0.defaultBilling', true)
            ->where('addresses.0.defaultShipping', false)
            ->where('addresses.1.city', 'München')
            ->where('addresses.1.defaultShipping', true));

    $this->delete(route('larasell.admin.customers.addresses.destroy', [$customer, $hamburg]))->assertRedirect();
    expect(CustomerAddress::query()->count())->toBe(1);
});

it('validates addresses', function () {
    // Act & Assert
    $this->post(route('larasell.admin.customers.addresses.store', detailsCompany()), addressInput(['street' => [], 'country_code' => 'Germany', 'city' => '']))
        ->assertSessionHasErrors(['street', 'country_code', 'city']);
});

it('refuses to change an address through another customer', function () {
    // Arrange
    $customer = detailsCompany();
    $this->post(route('larasell.admin.customers.addresses.store', $customer), addressInput());
    $address = CustomerAddress::query()->sole();

    // Act & Assert
    $this->delete(route('larasell.admin.customers.addresses.destroy', [detailsCompany(), $address]))->assertNotFound();
});

it('adds, edits and removes contacts of a company', function () {
    // Arrange
    $customer = detailsCompany();

    // Act
    $this->post(route('larasell.admin.customers.contacts.store', $customer), ['first_name' => 'Max', 'last_name' => 'Mustermann', 'job_title' => 'Einkauf'])->assertRedirect();
    $max = $customer->contacts()->sole();
    $this->put(route('larasell.admin.customers.contacts.update', [$customer, $max]), ['first_name' => 'Max', 'last_name' => 'Mustermann', 'job_title' => 'Geschäftsführung'])->assertRedirect();

    // Assert
    $this->get(route('larasell.admin.customers.show', $customer))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('contacts.0.name', 'Max Mustermann')
            ->where('contacts.0.jobTitle', 'Geschäftsführung'));
    $this->delete(route('larasell.admin.customers.contacts.destroy', [$customer, $max]))->assertRedirect();
    expect($customer->contacts()->count())->toBe(0);
});

it('explains that individuals have no contacts', function () {
    // Arrange
    $person = app(CreateCustomer::class)->handle(new CreateCustomerPayload(new PersonProfileData('Erika', 'Musterfrau'), CustomerSource::Admin));

    // Act & Assert
    $this->post(route('larasell.admin.customers.contacts.store', $person), ['first_name' => 'Max', 'last_name' => 'Mustermann'])
        ->assertSessionHasErrors('contact');
});

it('lists linked accounts and unlinks one, audited', function () {
    // Arrange
    $customer = detailsCompany();
    $max = app(AddCustomerContact::class)->handle(CustomerId::of($customer), new CustomerContactPayload('Max', 'Mustermann'));
    $user = CustomerDetailsTestUser::query()->create(['email' => 'max@muster.example']);
    $account = app(LinkCustomerAccount::class)->handle(CustomerId::of($customer), $user, CustomerContactId::of($max));

    // Act & Assert
    $this->get(route('larasell.admin.customers.show', $customer))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('accounts.0.label', 'max@muster.example')
            ->where('accounts.0.contact', 'Max Mustermann'));
    $this->delete(route('larasell.admin.customers.accounts.destroy', [$customer, $account]))->assertRedirect();
    expect($customer->accounts()->count())->toBe(0)
        ->and($user->fresh())->not->toBeNull()
        ->and(DB::table('larasell_audit_log')->orderBy('id')->pluck('event')->all())->toBe(['customer.created', 'customer.contact_added', 'customer.account_linked', 'customer.account_unlinked']);
});

it('puts a customer in groups and takes it out', function () {
    // Arrange
    $customer = detailsCompany();
    $wholesale = app(CreateCustomerGroup::class)->handle(new CustomerGroupPayload(CustomerGroupCode::of('wholesale'), new Translatable(['en' => 'Wholesale'])));

    // Act
    $this->post(route('larasell.admin.customers.groups.store', $customer), ['group' => $wholesale->id])->assertRedirect();

    // Assert
    $this->get(route('larasell.admin.customers.show', $customer))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('groups.0.name', 'Wholesale')
            ->where('availableGroups.0.label', 'Wholesale'));
    $this->delete(route('larasell.admin.customers.groups.destroy', [$customer, $wholesale]))->assertRedirect();
    expect($customer->groups()->count())->toBe(0);
});

it('audits addresses, contacts and group memberships changed in the panel', function () {
    // Arrange
    $customer = detailsCompany();
    $wholesale = app(CreateCustomerGroup::class)->handle(new CustomerGroupPayload(CustomerGroupCode::of('wholesale'), new Translatable(['en' => 'Wholesale'])));
    DB::table('larasell_audit_log')->delete();

    // Act
    $this->post(route('larasell.admin.customers.addresses.store', $customer), addressInput())->assertRedirect();
    $address = $customer->addresses()->sole();
    $this->delete(route('larasell.admin.customers.addresses.destroy', [$customer, $address]))->assertRedirect();
    $this->post(route('larasell.admin.customers.contacts.store', $customer), ['first_name' => 'Max', 'last_name' => 'Mustermann'])->assertRedirect();
    $contact = $customer->contacts()->sole();
    $this->delete(route('larasell.admin.customers.contacts.destroy', [$customer, $contact]))->assertRedirect();
    $this->post(route('larasell.admin.customers.groups.store', $customer), ['group' => $wholesale->id])->assertRedirect();
    $this->delete(route('larasell.admin.customers.groups.destroy', [$customer, $wholesale]))->assertRedirect();

    // Assert
    $records = DB::table('larasell_audit_log')->orderBy('id')->get(['event', 'subject_type', 'subject_id', 'actor_type']);
    expect($records->pluck('event')->all())->toBe([
        'customer.address_added',
        'customer.address_deleted',
        'customer.contact_added',
        'customer.contact_removed',
        'customer.joined_group',
        'customer.left_group',
    ])
        ->and($records->pluck('subject_type')->unique()->all())->toBe(['customer'])
        ->and($records->pluck('subject_id')->unique()->all())->toBe([(string) $customer->id])
        ->and($records->pluck('actor_type')->unique()->all())->not->toContain('unknown');
});

it('audits customer changes made outside the panel with an unknown actor', function () {
    // Arrange
    auth('larasell-admin')->logout();
    DB::table('larasell_audit_log')->delete();

    // Act
    $customer = detailsCompany();

    // Assert
    $record = DB::table('larasell_audit_log')->sole();
    expect($record->event)->toBe('customer.created')
        ->and($record->subject_id)->toBe((string) $customer->id)
        ->and($record->unknown_actor_reason)->not->toBeNull();
});
