<?php

namespace Larasell\Admin\Manufacturer\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Larasell\Admin\Platform\ContentLocale\CurrentContentLocale;
use Larasell\Admin\Platform\Search\Data\SearchMatchData;
use Larasell\Admin\Platform\Search\Data\SearchResultData;
use Larasell\Admin\Platform\Search\Http\Requests\SearchRequest;
use Larasell\Larasell\Catalog\Manufacturers\Models\Manufacturer;
use Larasell\Larasell\Enums\Locale;

/** The command menu's manufacturer search, by name or email. */
class SearchManufacturersController
{
    public function __invoke(SearchRequest $request, #[CurrentContentLocale] Locale $locale): JsonResponse
    {
        $query = $request->toQuery($locale);

        $results = Manufacturer::query()
            ->where(function ($where) use ($query): void {
                $query->orWhereMatches($where, 'name');
                $query->orWhereMatches($where, 'email');
            })
            ->tap(fn ($manufacturers) => $query->orderByMatching($manufacturers, 'name'))
            ->orderBy('id')
            ->limit($query->limit)
            ->get()
            ->map(fn (Manufacturer $manufacturer): SearchResultData => new SearchResultData(
                $manufacturer->id,
                $manufacturer->name,
                route('larasell.admin.manufacturers.show', $manufacturer),
                $query->matches($manufacturer->name) || $manufacturer->email === null || ! $query->matches($manufacturer->email)
                    ? null
                    : new SearchMatchData(__('Email'), $manufacturer->email),
            ));

        return response()->json(['results' => $results->values()]);
    }
}
