<?php

namespace Larasell\Admin\Http\Controllers\Customers;

use Illuminate\Http\JsonResponse;
use Larasell\Admin\ContentLocale\CurrentContentLocale;
use Larasell\Admin\Data\ViewModel\Search\SearchResultData;
use Larasell\Admin\Http\Requests\SearchRequest;
use Larasell\Admin\Search\Customers\CustomerSearch;
use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\Models\Customer;

/**
 * The command menu's customer search, by name.
 */
class SearchCustomersController
{
    public function __invoke(SearchRequest $request, CustomerSearch $search, #[CurrentContentLocale] Locale $locale): JsonResponse
    {
        $results = $search->search($request->toQuery($locale))->map(fn (Customer $customer): SearchResultData => new SearchResultData(
            $customer->id,
            $customer->fullName(),
            route('larasell.admin.customers.edit', $customer),
            null,
        ));

        return response()->json(['results' => $results->values()]);
    }
}
