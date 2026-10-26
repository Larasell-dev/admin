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
            ->component('Customers', shouldExist: false)
            ->where('customers.total', 2)
            ->where('customers.data.0.name', 'Alan Turing')
            ->where('customers.data.0.ordersCount', 0)
            ->where('customers.data.1.name', 'Ada Lovelace')
            ->where('customers.data.1.ordersCount', 2)
            ->where('customers.data.1.createdAt', '2 days ago'));
});
