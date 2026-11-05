<?php

namespace Larasell\Admin\Order\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Larasell\Admin\Order\Search\OrderSearch;
use Larasell\Admin\Platform\ContentLocale\CurrentContentLocale;
use Larasell\Admin\Platform\Search\Data\SearchMatchData;
use Larasell\Admin\Platform\Search\Data\SearchResultData;
use Larasell\Admin\Platform\Search\Http\Requests\SearchRequest;
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
