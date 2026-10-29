<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;
use Larasell\Admin\Models\Admin;
use Larasell\Larasell\Enums\Currency;
use Larasell\Larasell\Enums\OrderStatus;
use Larasell\Larasell\Enums\PaymentStatus;
use Larasell\Larasell\Enums\TaxPriceMode;
use Larasell\Larasell\Models\Customer;
use Larasell\Larasell\Models\Order;
use Larasell\Larasell\Price;
use Larasell\Larasell\Translatable;

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

    expect(Route::has('larasell.admin.orders.show'))->toBeFalse();

    // The routes aren't registered, so there are no route names to use.
    $this->get('/admin/orders')->assertNotFound(); // @phpstan-ignore larasell.preferRouteHelper
    $this->get('/admin/orders/1')->assertNotFound(); // @phpstan-ignore larasell.preferRouteHelper
    $this->get(route('larasell.admin.home'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('navigation', fn ($items) => collect($items)->pluck('label')->all() === ['Home', 'Products', 'Categories', 'Customers', 'Settings']));
});

/**
 * The order from the page's mockup: two items, a discount, shipping and 19% VAT on top, paid in cash.
 */
function createPlacedOrder(array $attributes = []): Order
{
    $address = [
        'country' => 'DE',
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'company' => 'Acme GmbH',
        'street' => ['Hauptstraße 1', 'Hinterhaus'],
        'city' => 'Berlin',
        'postcode' => '10115',
    ];

    $order = createOrder('2026-000123', [
        'customer_name' => 'Jane Doe',
        'customer_email' => 'jane@example.com',
        'customer_phone' => '+49 170 1234567',
        'status' => OrderStatus::Paid,
        'billing_address' => $address,
        'shipping_address' => [...$address, 'company' => null, 'street' => 'Packstation 105', 'state' => 'Berlin'],
        'subtotal' => Price::of(4800),
        'discount_total' => Price::of(500),
        'shipping_option_name' => 'DHL Standard',
        'shipping_price' => Price::of(450),
        'tax_price_mode' => TaxPriceMode::Exclusive,
        'tax_total' => Price::of(912),
        'tax_snapshot' => ['components' => [['name' => 'VAT', 'rate' => '19.0000', 'amount' => ['amount' => '912']]]],
        'total' => Price::of(5662),
        'created_at' => Carbon::parse('2026-10-28 14:30'),
        ...$attributes,
    ]);

    $order->items()->create([
        'product_name' => Translatable::fromString('Classic Tee'),
        'variant_name' => 'Blue / M',
        'unit_price' => Price::of(1900),
        'quantity' => 2,
        'total' => Price::of(3800),
    ]);
    $order->items()->create([
        'product_name' => Translatable::fromString('Coffee Mug'),
        'unit_price' => Price::of(1000),
        'quantity' => 1,
        'total' => Price::of(1000),
    ]);

    return $order;
}

it('shows an order with its items, summary, customer, addresses, payments and shipping', function () {
    signInOrdersAdmin();
    $order = createPlacedOrder();
    $order->payments()->create(['provider' => 'manual', 'method' => 'cash', 'status' => PaymentStatus::Succeeded, 'reference' => '2026-REF-1', 'amount' => Price::of(5662)]);

    $this->get(route('larasell.admin.orders.show', $order))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Order/Show')
            ->where('indexUrl', route('larasell.admin.orders.index'))
            ->where('customerUrl', null)
            ->where('order.number', '2026-000123')
            ->where('order.status', ['label' => 'Paid', 'variant' => 'success'])
            ->where('order.placedAt', 'Oct 28, 2026')
            ->where('order.items', [
                ['name' => 'Classic Tee', 'variant' => 'Blue / M', 'quantity' => 2, 'unitPrice' => '€19.00', 'total' => '€38.00'],
                ['name' => 'Coffee Mug', 'variant' => null, 'quantity' => 1, 'unitPrice' => '€10.00', 'total' => '€10.00'],
            ])
            ->where('order.subtotal', '€48.00')
            ->where('order.discount', '€5.00')
            ->where('order.shipping', ['label' => 'DHL Standard', 'amount' => '€4.50'])
            ->where('order.taxes', [['label' => 'VAT (19%)', 'amount' => '€9.12']])
            ->where('order.taxIncluded', false)
            ->where('order.total', '€56.62')
            ->where('order.customerName', 'Jane Doe')
            ->where('order.customerEmail', 'jane@example.com')
            ->where('order.customerPhone', '+49 170 1234567')
            ->where('order.billingAddress', ['name' => 'Jane Doe', 'company' => 'Acme GmbH', 'street' => ['Hauptstraße 1', 'Hinterhaus'], 'postcode' => '10115', 'city' => 'Berlin', 'state' => null, 'country' => 'Germany'])
            ->where('order.shippingAddress', ['name' => 'Jane Doe', 'company' => null, 'street' => ['Packstation 105'], 'postcode' => '10115', 'city' => 'Berlin', 'state' => 'Berlin', 'country' => 'Germany'])
            ->where('order.payments', [
                ['method' => 'cash', 'status' => ['label' => 'Succeeded', 'variant' => 'success'], 'reference' => '2026-REF-1', 'amount' => '€56.62'],
            ]));
});

it('shows an order without discount, shipping, tax, addresses or payments', function () {
    signInOrdersAdmin();
    $order = createOrder('1001', ['subtotal' => Price::of(1000), 'total' => Price::of(1000)]);

    $this->get(route('larasell.admin.orders.show', $order))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('order.status', ['label' => 'Pending payment', 'variant' => 'warning'])
            ->where('order.discount', null)
            ->where('order.shipping', null)
            ->where('order.taxes', [])
            ->where('order.customerPhone', null)
            ->where('order.billingAddress', null)
            ->where('order.shippingAddress', null)
            ->where('order.payments', []));
});

it('shows the tax that prices include, and a tax total without a breakdown', function () {
    signInOrdersAdmin();
    $order = createPlacedOrder(['tax_price_mode' => TaxPriceMode::Inclusive, 'tax_snapshot' => null, 'total' => Price::of(4750)]);

    $this->get(route('larasell.admin.orders.show', $order))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('order.taxes', [['label' => 'Tax', 'amount' => '€9.12']])
            ->where('order.taxIncluded', true)
            ->where('order.total', '€47.50'));
});

it('lists the order\'s payments, newest first', function () {
    signInOrdersAdmin();
    $order = createPlacedOrder();
    $order->payments()->create(['provider' => 'stripe', 'method' => 'card', 'status' => PaymentStatus::Failed, 'amount' => Price::of(5662), 'created_at' => now()->subHour()]);
    $order->payments()->create(['provider' => 'manual', 'method' => 'cash', 'status' => PaymentStatus::Pending, 'amount' => Price::of(5662)]);

    $this->get(route('larasell.admin.orders.show', $order))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('order.payments.0.method', 'cash')
            ->where('order.payments.0.status', ['label' => 'Pending', 'variant' => 'warning'])
            ->where('order.payments.0.reference', null)
            ->where('order.payments.1.method', 'card')
            ->where('order.payments.1.status', ['label' => 'Failed', 'variant' => 'danger']));
});

it('links the order to its customer', function () {
    signInOrdersAdmin();
    $customer = Customer::create(['first_name' => 'Jane', 'last_name' => 'Doe']);
    $order = createPlacedOrder(['customer_id' => $customer->id]);

    $this->get(route('larasell.admin.orders.show', $order))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('customerUrl', route('larasell.admin.customers.edit', $customer)));
});

it('does not link the order to its customer while the customers are turned off', function () {
    $this->bootingWith(fn () => config(['larasell.admin.customers.enabled' => false]));
    signInOrdersAdmin();
    $customer = Customer::create(['first_name' => 'Jane', 'last_name' => 'Doe']);
    $order = createPlacedOrder(['customer_id' => $customer->id]);

    $this->get(route('larasell.admin.orders.show', $order))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('customerUrl', null));
});

it('links each order in the table to its page', function () {
    signInOrdersAdmin();
    $order = createOrder('1001');

    $this->get(route('larasell.admin.orders.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('table.rows.0._link', route('larasell.admin.orders.show', $order)));
});

it('shows the not found page for an order that does not exist', function () {
    signInOrdersAdmin();

    $this->get(route('larasell.admin.orders.show', 999))->assertNotFound();
});

it('redirects guests away from an order', function () {
    $order = createOrder('1001');

    $this->get(route('larasell.admin.orders.show', $order))->assertRedirect(route('larasell.admin.login'));
});
