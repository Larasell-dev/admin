<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Larasell\Admin\Models\Admin;
use Larasell\Larasell\Models\Customer;

it('lists customers with their order count, newest first', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);

    $older = Customer::create(['first_name' => 'Ada', 'last_name' => 'Lovelace', 'created_at' => now()->subDays(2)]);
    Customer::create(['first_name' => 'Alan', 'last_name' => 'Turing']);

    foreach (['1', '2'] as $number) {
        DB::table('larasell_orders')->insert([
            'number' => $number,
            'customer_id' => $older->id,
            'customer_email' => 'ada@example.com',
            'customer_name' => 'Ada Lovelace',
            'public_id' => "order-{$number}",
            'currency' => 'EUR',
            'subtotal' => '{}',
            'total' => '{}',
            'discount_total' => '{}',
            'discounts' => '[]',
            'metadata' => '{}',
        ]);
    }

    $this->actingAs($admin, 'larasell-admin')
        ->get(route('larasell.admin.customers.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Customer/Index')
            ->where('customers.total', 2)
            ->where('customers.data.0.name', 'Alan Turing')
            ->where('customers.data.0.ordersCount', 0)
            ->where('customers.data.1.name', 'Ada Lovelace')
            ->where('customers.data.1.ordersCount', 2)
            ->where('customers.data.1.createdAt', '2 days ago'));
});

it('links each customer to their edit page and offers to add one', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $ada = Customer::create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);

    $this->actingAs($admin, 'larasell-admin')
        ->get(route('larasell.admin.customers.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('createUrl', route('larasell.admin.customers.create'))
            ->where('customers.data.0.editUrl', route('larasell.admin.customers.edit', $ada)));
});

it('opens the new customer page', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);

    $this->actingAs($admin, 'larasell-admin')
        ->get(route('larasell.admin.customers.create'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Customer/Create')
            ->where('storeUrl', route('larasell.admin.customers.store'))
            ->where('indexUrl', route('larasell.admin.customers.index')));
});

it('adds a customer and opens their page', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);

    $response = $this->actingAs($admin, 'larasell-admin')
        ->post(route('larasell.admin.customers.store'), ['first_name' => 'Grace', 'last_name' => 'Hopper']);

    $grace = Customer::query()->sole();
    $response->assertRedirect(route('larasell.admin.customers.edit', $grace));
    expect($grace->first_name)->toBe('Grace')
        ->and($grace->last_name)->toBe('Hopper');
});

it('rejects a customer without a first or last name', function (string $method, Closure $url) {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $ada = Customer::create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);

    $this->actingAs($admin, 'larasell-admin')
        ->{$method}($url($ada), ['first_name' => '', 'last_name' => str_repeat('a', 256)])
        ->assertSessionHasErrors(['first_name', 'last_name']);

    expect(Customer::query()->sole()->only(['first_name', 'last_name']))
        ->toBe(['first_name' => 'Ada', 'last_name' => 'Lovelace']);
})->with([
    'creating' => ['post', fn () => route('larasell.admin.customers.store')],
    'updating' => ['put', fn (Customer $customer) => route('larasell.admin.customers.update', $customer)],
]);

it('opens a customer to edit', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    Customer::create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);
    $alan = Customer::create(['first_name' => 'Alan', 'last_name' => 'Turing']);

    $this->actingAs($admin, 'larasell-admin')
        ->get(route('larasell.admin.customers.edit', $alan))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Customer/Edit')
            ->where('customer', ['name' => 'Alan Turing', 'firstName' => 'Alan', 'lastName' => 'Turing'])
            ->where('updateUrl', route('larasell.admin.customers.update', $alan))
            ->where('indexUrl', route('larasell.admin.customers.index')));
});

it('updates only the edited customer', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $ada = Customer::create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);
    $alan = Customer::create(['first_name' => 'Alan', 'last_name' => 'Turing']);

    $this->actingAs($admin, 'larasell-admin')
        ->put(route('larasell.admin.customers.update', $alan), ['first_name' => 'Alan M.', 'last_name' => 'Turing-Smith'])
        ->assertRedirect(route('larasell.admin.customers.edit', $alan));

    expect($alan->refresh()->fullName())->toBe('Alan M. Turing-Smith')
        ->and($ada->refresh()->fullName())->toBe('Ada Lovelace');
});

it('shows a missing customer as not found', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);

    $this->actingAs($admin, 'larasell-admin')
        ->get(route('larasell.admin.customers.edit', 999))
        ->assertNotFound();
});
