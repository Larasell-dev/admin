<?php

namespace Larasell\Admin\Customer\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Larasell\Admin\Customer\Search\CustomerSearch;
use Larasell\Admin\Platform\ContentLocale\CurrentContentLocale;
use Larasell\Admin\Platform\Search\Data\SearchMatchData;
use Larasell\Admin\Platform\Search\Data\SearchResultData;
use Larasell\Admin\Platform\Search\Http\Requests\SearchRequest;
use Larasell\Larasell\Customers\Models\Customer;
use Larasell\Larasell\Enums\Locale;

/** The command menu's customer search: each customer's name, and its number or email when that matched. */
class SearchCustomersController
{
    public function __invoke(SearchRequest $request, CustomerSearch $search, #[CurrentContentLocale] Locale $locale): JsonResponse
    {
        $query = $request->toQuery($locale);

        $results = $search->search($query)->map(function (Customer $customer) use ($query): SearchResultData {
            $name = $customer->displayName();
            $email = $customer->personProfile?->email?->value();

            return new SearchResultData(
                $customer->id,
                $name,
                route('larasell.admin.customers.show', $customer),
                match (true) {
                    $query->matches($name) => null,
                    $email !== null && $query->matches($email) => new SearchMatchData(__('Email'), $email),
                    $query->matches($customer->customer_number) => new SearchMatchData(__('Number'), $customer->customer_number),
                    default => null,
                },
            );
        });

        return response()->json(['results' => $results->values()]);
    }
}
