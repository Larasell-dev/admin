<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;
use Larasell\Admin\Models\Admin;
use Larasell\Larasell\Enums\Currency;
use Larasell\Larasell\Enums\OrderStatus;
use Larasell\Larasell\Models\Order;
use Larasell\Larasell\Price;

function createOrder(string $number, array $attributes = []): Order
{
    return Order::create([
        'number' => $number,
        'customer_email' => 'ada@example.com',
        'customer_name' => 'Ada Lovelace',
        'currency' => Currency::EUR,
        'subtotal' => Price::of(0),
        'total' => Price::of(0),
        ...$attributes,
    ]);
}

function signInOrdersAdmin(): Admin
{
    $admin = Admin::create(['name' => 'Test Admin', 'email' => 'admin@example.com', 'password' => 'password']);
    test()->actingAs($admin, 'larasell-admin');

    return $admin;
}

it('lists orders with their customer, status and total, newest first', function () {
    signInOrdersAdmin();
    createOrder('1001', ['customer_name' => 'Ada Lovelace', 'status' => OrderStatus::Paid, 'total' => Price::of(1250), 'created_at' => now()->subDays(2)]);
    createOrder('1002', ['customer_name' => 'Alan Turing', 'currency' => Currency::USD, 'total' => Price::of(99)]);

    $this->get(route('larasell.admin.orders.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('TablePage')
            ->where('table.title', 'Orders')
            ->where('table.columns', [
                ['name' => 'number', 'label' => 'Order', 'type' => 'text'],
                ['name' => 'customer_name', 'label' => 'Customer', 'type' => 'text'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'badge'],
                ['name' => 'total', 'label' => 'Total', 'type' => 'text'],
                ['name' => 'created_at', 'label' => 'Date', 'type' => 'text'],
            ])
            ->where('table.pagination.total', 2)
            ->where('table.rows.0.number', '1002')
            ->where('table.rows.0.customer_name', 'Alan Turing')
            ->where('table.rows.0.status', 'Pending payment')
            ->where('table.rows.0.total', '$0.99')
            ->where('table.rows.1.number', '1001')
            ->where('table.rows.1.status', 'Paid')
            ->where('table.rows.1.total', '€12.50')
            ->where('table.rows.1.created_at', '2 days ago'));
});

it('colours each order status', function (OrderStatus $status, string $label, string $variant) {
    signInOrdersAdmin();
    createOrder('1001', ['status' => $status]);

    $this->get(route('larasell.admin.orders.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('table.rows.0.status', $label)
            ->where('table.rows.0._variants.status', $variant));
})->with([
    'pending payment' => [OrderStatus::PendingPayment, 'Pending payment', 'warning'],
    'paid' => [OrderStatus::Paid, 'Paid', 'success'],
    'payment failed' => [OrderStatus::PaymentFailed, 'Payment failed', 'danger'],
    'fulfilled' => [OrderStatus::Fulfilled, 'Fulfilled', 'info'],
    'cancelled' => [OrderStatus::Cancelled, 'Cancelled', 'neutral'],
]);

it('explains that there are no orders yet', function () {
    signInOrdersAdmin();

    $this->get(route('larasell.admin.orders.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('table.pagination.total', 0)
            ->where('table.emptyState.title', 'No orders yet')
            ->where('table.emptyState.icon', 'Receipt'));
});

it('redirects guests away from the orders', function () {
    $this->get(route('larasell.admin.orders.index'))->assertRedirect(route('larasell.admin.login'));
});

it('shows Orders in the navigation between Categories and Customers', function () {
    signInOrdersAdmin();

    $this->get(route('larasell.admin.home'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('navigation', fn ($items) => collect($items)->pluck('label')->all() === ['Home', 'Products', 'Categories', 'Orders', 'Customers', 'Settings']));
});

it('turns the orders off when disabled in the config', function () {
    $this->bootingWith(fn () => config(['larasell.admin.orders.enabled' => false]));
    signInOrdersAdmin();

    expect(Route::has('larasell.admin.orders.index'))->toBeFalse();

    // The route isn't registered, so there is no route name to use.
    $this->get('/admin/orders')->assertNotFound(); // @phpstan-ignore larasell.preferRouteHelper
    $this->get(route('larasell.admin.home'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('navigation', fn ($items) => collect($items)->pluck('label')->all() === ['Home', 'Products', 'Categories', 'Customers', 'Settings']));
});
