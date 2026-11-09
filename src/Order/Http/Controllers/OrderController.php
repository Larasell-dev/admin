<?php

namespace Larasell\Admin\Order\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\Order\Data\OrderDetailData;
use Larasell\Admin\Order\Data\OrderPageData;
use Larasell\Larasell\Models\Order;
use Laravel\Head\Facades\Head;

/**
 * The orders' pages besides their table. Orders record what was bought, so they can only be shown.
 */
class OrderController
{
    public function show(Order $order): Response
    {
        Head::title(__('Order #:number', ['number' => $order->number]));

        return Inertia::render('Order/Pages/Show', new OrderPageData(
            order: OrderDetailData::fromModel($order),
            indexUrl: route('larasell.admin.orders.index'),
        ));
    }
}
