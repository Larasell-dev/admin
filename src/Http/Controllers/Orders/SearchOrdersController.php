<?php

namespace Larasell\Admin\Http\Controllers\Orders;

use Illuminate\Http\JsonResponse;
use Larasell\Admin\ContentLocale\CurrentContentLocale;
use Larasell\Admin\Data\ViewModel\Search\SearchMatchData;
use Larasell\Admin\Data\ViewModel\Search\SearchResultData;
use Larasell\Admin\Http\Requests\SearchRequest;
use Larasell\Admin\Order\Search\OrderSearch;
use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\Models\Order;

/**
 * The command menu's order search: each order's number, and its customer's name or email when
 * that's what matched.
 */
class SearchOrdersController
{
    public function __invoke(SearchRequest $request, OrderSearch $search, #[CurrentContentLocale] Locale $locale): JsonResponse
    {
        $query = $request->toQuery($locale);

        $results = $search->search($query)->map(fn (Order $order): SearchResultData => new SearchResultData(
            $order->id,
            $order->number,
            route('larasell.admin.orders.show', $order),
            match (true) {
                $query->matches($order->number) => null,
                $query->matches($order->customer_name) => new SearchMatchData('Customer', $order->customer_name),
                $query->matches($order->customer_email) => new SearchMatchData('Email', $order->customer_email),
                default => null,
            },
        ));

        return response()->json(['results' => $results->values()]);
    }
}
