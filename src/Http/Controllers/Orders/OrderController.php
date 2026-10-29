<?php

namespace Larasell\Admin\Http\Controllers\Orders;

use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\Data\View\Orders\OrderPageData;
use Larasell\Admin\Data\ViewModel\Orders\OrderDetailData;
use Larasell\Larasell\Models\Order;
use Laravel\Head\Facades\Head;

/**
 * The orders' pages besides their table. Orders record what was bought, so they can only be shown.
 */
class OrderController
{
    public function show(Order $order): Response
    {
        Head::title("Order #{$order->number}");

        return Inertia::render('Order/Show', new OrderPageData(
            order: OrderDetailData::fromModel($order),
            indexUrl: route('larasell.admin.orders.index'),
            customerUrl: $order->customer_id !== null && config('larasell.admin.customers.enabled')
                ? route('larasell.admin.customers.edit', $order->customer_id)
                : null,
        ));
    }
}
