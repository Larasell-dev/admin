<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Larasell\Admin\Customer\Tables\CustomerGroups;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Larasell\Customers\Actions\AddCustomerToGroup;
use Larasell\Larasell\Customers\Actions\CreateCustomer;
use Larasell\Larasell\Customers\Actions\CreateCustomerPayload;
use Larasell\Larasell\Customers\Actions\PersonProfileData;
use Larasell\Larasell\Customers\CustomerGroupId;
use Larasell\Larasell\Customers\CustomerId;
use Larasell\Larasell\Customers\Enums\CustomerSource;
use Larasell\Larasell\Customers\Models\CustomerGroup;

beforeEach(function () {
    $this->actingAs(Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']), 'larasell-admin');
});

it('creates, lists, edits and deletes customer groups, audited', function () {
    // Act
    $response = $this->post(route('larasell.admin.customer-groups.store'), ['code' => 'wholesale', 'name' => 'Wholesale', 'description' => 'Resellers']);
    $group = CustomerGroup::query()->sole();
    $response->assertRedirect(route('larasell.admin.customer-groups.edit', $group));
    $this->put(route('larasell.admin.customer-groups.update', $group), ['code' => 'wholesale', 'name' => 'Wholesale Premium'])->assertRedirect();

    // Assert
    $this->get(route('larasell.admin.customer-groups.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Platform/Tables/Pages/TablePage')
            ->where('table.title', 'Customer groups')
            ->where('table.rows.0.code', 'wholesale')
            ->where('table.rows.0.name', 'Wholesale Premium')
            ->where('table.rows.0.customers_count', '0'));
    $this->get(route('larasell.admin.customer-groups.edit', $group))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Customer/Pages/EditGroup')
            ->where('group.code', 'wholesale')
            ->where('group.description', null));
    $this->delete(route('larasell.admin.customer-groups.destroy', $group), ['confirmation' => 'Wholesale'])->assertSessionHasErrors('confirmation');
    $this->delete(route('larasell.admin.customer-groups.destroy', $group), ['confirmation' => ' wholesale '])->assertRedirect(route('larasell.admin.customer-groups.index'));
    expect(CustomerGroup::query()->count())->toBe(0)
        ->and(DB::table('larasell_audit_log')->orderBy('id')->pluck('event')->all())->toBe(['customer_group.created', 'customer_group.updated', 'customer_group.deleted']);
});

it('refuses a taken code and keeps groups with members', function () {
    // Arrange
    $this->post(route('larasell.admin.customer-groups.store'), ['code' => 'vip', 'name' => 'VIP']);
    $group = CustomerGroup::query()->sole();
    $customer = app(CreateCustomer::class)->handle(new CreateCustomerPayload(new PersonProfileData('A', 'B'), CustomerSource::Admin));
    app(AddCustomerToGroup::class)->handle(CustomerId::of($customer), CustomerGroupId::of($group));

    // Act & Assert
    $this->post(route('larasell.admin.customer-groups.store'), ['code' => 'vip', 'name' => 'Other'])->assertSessionHasErrors('code');
    $this->post(route('larasell.admin.customer-groups.store'), ['code' => 'Not a code', 'name' => 'Other'])->assertSessionHasErrors('code');
    $this->delete(route('larasell.admin.customer-groups.destroy', $group), ['confirmation' => 'vip'])->assertSessionHasErrors('group');
    expect($group->fresh())->not->toBeNull();
});

it('deletes an empty group from the table once its code is typed, audited', function () {
    // Arrange
    $this->post(route('larasell.admin.customer-groups.store'), ['code' => 'vip', 'name' => 'VIP']);
    $group = CustomerGroup::query()->sole();
    $table = CustomerGroups::make();
    $url = $table->actionUrl($table->action('delete'), $group);

    // Act & Assert
    $this->post($url, ['confirmation' => 'VIP'])->assertSessionHasErrors('confirmation');
    $this->post($url, ['confirmation' => 'vip'])->assertRedirect();
    expect(CustomerGroup::query()->count())->toBe(0)
        ->and(DB::table('larasell_audit_log')->orderBy('id')->pluck('event')->all())->toBe(['customer_group.created', 'customer_group.deleted']);
});

it('refuses to delete a group with members from the table', function () {
    // Arrange
    $this->post(route('larasell.admin.customer-groups.store'), ['code' => 'vip', 'name' => 'VIP']);
    $group = CustomerGroup::query()->sole();
    $customer = app(CreateCustomer::class)->handle(new CreateCustomerPayload(new PersonProfileData('A', 'B'), CustomerSource::Admin));
    app(AddCustomerToGroup::class)->handle(CustomerId::of($customer), CustomerGroupId::of($group));
    $table = CustomerGroups::make();

    // Act & Assert
    $this->post($table->actionUrl($table->action('delete'), $group), ['confirmation' => 'vip'])->assertSessionHasErrors('group');
    expect($group->fresh())->not->toBeNull();
});
