<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
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
            ->component('TablePage')
            ->where('table.title', 'Customers')
            ->where('table.columns', [
                ['name' => 'name', 'label' => 'Name', 'type' => 'text'],
                ['name' => 'orders_count', 'label' => 'Orders', 'type' => 'text'],
                ['name' => 'created_at', 'label' => 'Created', 'type' => 'text'],
            ])
            ->where('table.pagination.total', 2)
            ->where('table.rows.0.name', 'Alan Turing')
            ->where('table.rows.0.orders_count', '0')
            ->where('table.rows.1.name', 'Ada Lovelace')
            ->where('table.rows.1.orders_count', '2')
            ->where('table.rows.1.created_at', '2 days ago'));
});

it('links each customer to their edit page and offers to add one', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $ada = Customer::create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);

    $this->actingAs($admin, 'larasell-admin')
        ->get(route('larasell.admin.customers.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('table.headerActions.0.label', 'Add customer')
            ->where('table.headerActions.0.url', route('larasell.admin.customers.create'))
            ->where('table.rows.0._link', route('larasell.admin.customers.edit', $ada)));
});

it('offers to add the first customer when there are none', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);

    $this->actingAs($admin, 'larasell-admin')
        ->get(route('larasell.admin.customers.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('table.pagination.total', 0)
            ->where('table.emptyState.title', 'No customers yet')
            ->where('table.emptyState.icon', 'Group')
            ->where('table.emptyState.actions.0.url', route('larasell.admin.customers.create')));
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

it('offers to delete each customer', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    Customer::create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);

    $this->actingAs($admin, 'larasell-admin')
        ->get(route('larasell.admin.customers.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('table.rows.0._actions.0.label', 'Delete')
            ->where('table.rows.0._actions.0.method', 'post')
            ->where('table.rows.0._actions.0.destructive', true)
            ->where('table.rows.0._actions.0.icon', 'DeleteBin')
            ->where('table.rows.0._actions.0.confirm.title', 'Delete customer'));
});

it('deletes a customer and keeps the others', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    Customer::create(['first_name' => 'Ada', 'last_name' => 'Lovelace', 'created_at' => now()->subDay()]);
    Customer::create(['first_name' => 'Alan', 'last_name' => 'Turing']);
    $this->actingAs($admin, 'larasell-admin');

    // The newest customer comes first, so this is Alan's delete action.
    $delete = $this->get(route('larasell.admin.customers.index'))->inertiaProps('table.rows.0._actions.0.url');

    $this->from(route('larasell.admin.customers.index'))->post($delete)->assertRedirect(route('larasell.admin.customers.index'));

    expect(Customer::query()->pluck('last_name')->all())->toBe(['Lovelace']);
});

it('keeps the orders of a deleted customer, no longer linked to them', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $alan = Customer::create(['first_name' => 'Alan', 'last_name' => 'Turing']);
    DB::table('larasell_orders')->insert([
        'number' => '1',
        'customer_id' => $alan->id,
        'customer_email' => 'alan@example.com',
        'customer_name' => 'Alan Turing',
        'public_id' => 'order-1',
        'currency' => 'EUR',
        'subtotal' => '{}',
        'total' => '{}',
        'discount_total' => '{}',
        'discounts' => '[]',
        'metadata' => '{}',
    ]);
    $this->actingAs($admin, 'larasell-admin');

    $delete = $this->get(route('larasell.admin.customers.index'))->inertiaProps('table.rows.0._actions.0.url');
    $this->post($delete);

    expect(DB::table('larasell_orders')->get(['customer_id', 'customer_name'])->map(fn ($order) => (array) $order)->all())
        ->toBe([['customer_id' => null, 'customer_name' => 'Alan Turing']]);
});

it('unlinks the user accounts of a deleted customer and keeps the others', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $ada = Customer::create(['first_name' => 'Ada', 'last_name' => 'Lovelace', 'created_at' => now()->subDay()]);
    $alan = Customer::create(['first_name' => 'Alan', 'last_name' => 'Turing']);
    DB::table('larasell_customer_user')->insert([['customer_id' => $ada->id, 'user_id' => 7], ['customer_id' => $alan->id, 'user_id' => 8]]);
    $this->actingAs($admin, 'larasell-admin');

    // The newest customer comes first, so this is Alan's delete action.
    $delete = $this->get(route('larasell.admin.customers.index'))->inertiaProps('table.rows.0._actions.0.url');
    $this->post($delete);

    expect(DB::table('larasell_customer_user')->pluck('user_id')->all())->toBe([7]);
});

it('shows a missing customer as not found', function () {
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);

    $this->actingAs($admin, 'larasell-admin')
        ->get(route('larasell.admin.customers.edit', 999))
        ->assertNotFound();
});

it('turns the customers off when disabled in the config', function () {
    $this->bootingWith(fn () => config(['larasell.admin.customers.enabled' => false]));
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    $ada = Customer::create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);
    $this->actingAs($admin, 'larasell-admin');

    expect(Route::has('larasell.admin.customers.index'))->toBeFalse();

    // The routes aren't registered, so there are no route names to use.
    $this->get('/admin/customers')->assertNotFound(); // @phpstan-ignore larasell.preferRouteHelper
    $this->get('/admin/customers/create')->assertNotFound(); // @phpstan-ignore larasell.preferRouteHelper
    $this->post('/admin/customers', ['first_name' => 'Grace', 'last_name' => 'Hopper'])->assertNotFound(); // @phpstan-ignore larasell.preferRouteHelper
    $this->get("/admin/customers/{$ada->id}/edit")->assertNotFound(); // @phpstan-ignore larasell.preferRouteHelper
    $this->put("/admin/customers/{$ada->id}", ['first_name' => 'Ada', 'last_name' => 'King'])->assertNotFound(); // @phpstan-ignore larasell.preferRouteHelper
    $this->get(route('larasell.admin.home'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('navigation', fn ($items) => collect($items)->pluck('label')->all() === ['Home', 'Products', 'Categories', 'Settings']));

    expect(Customer::query()->pluck('last_name')->all())->toBe(['Lovelace']);
});
