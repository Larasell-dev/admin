<?php

namespace Larasell\Admin\Customer\Search;

use Illuminate\Support\Collection;
use Larasell\Admin\Platform\Search\SearchQuery;
use Larasell\Larasell\Customers\Models\Customer;

/**
 * Searches the customer tables, newest first, so it needs no search engine.
 */
class DatabaseCustomerSearch implements CustomerSearch
{
    public function search(SearchQuery $query): Collection
    {
        return Customer::query()
            ->with(['personProfile', 'companyProfile'])
            ->where(function ($where) use ($query): void {
                $query->orWhereMatches($where, 'customer_number');
                $where->orWhereHas('personProfile', function ($person) use ($query): void {
                    $person->where(function ($inner) use ($query): void {
                        $query->orWhereMatches($inner, 'first_name', 'last_name');
                        $query->orWhereMatches($inner, 'email');
                    });
                });
                $where->orWhereHas('companyProfile', fn ($company) => $company->where(fn ($inner) => $query->orWhereMatches($inner, 'legal_name')));
            })
            ->latest('id')
            ->limit($query->limit)
            ->get();
    }
}
