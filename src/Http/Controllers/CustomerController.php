<?php

namespace Larasell\Admin\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Larasell\Larasell\Models\Customer;

class CustomerController
{
    public function index(Request $request): Response
    {
        $perPage = in_array($request->integer('per_page'), [25, 50, 100], true) ? $request->integer('per_page') : 25;

        return Inertia::render('Customers', [
            'customers' => Customer::query()
                ->withCount('orders')
                ->latest()
                ->paginate($perPage)
                ->through(fn (Customer $customer): array => [
                    'id' => $customer->id,
                    'name' => $customer->fullName(),
                    'ordersCount' => $customer->orders_count,
                    'createdAt' => $customer->getAttribute('created_at')?->diffForHumans(),
                ]),
        ]);
    }
}
