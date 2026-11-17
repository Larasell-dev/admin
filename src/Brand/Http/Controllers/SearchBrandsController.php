<?php

namespace Larasell\Admin\Brand\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Larasell\Admin\Platform\ContentLocale\CurrentContentLocale;
use Larasell\Admin\Platform\ContentLocale\Locales\AvailableLocales;
use Larasell\Admin\Platform\Search\Data\SearchMatchData;
use Larasell\Admin\Platform\Search\Data\SearchResultData;
use Larasell\Admin\Platform\Search\Http\Requests\SearchRequest;
use Larasell\Larasell\Catalog\Brands\Models\Brand;
use Larasell\Larasell\Enums\Locale;

/** The command menu's brand search, by name in any language or by slug. */
class SearchBrandsController
{
    public function __invoke(SearchRequest $request, AvailableLocales $locales, #[CurrentContentLocale] Locale $locale): JsonResponse
    {
        $query = $request->toQuery($locale);

        $results = Brand::query()
            ->where(function ($where) use ($query, $locales): void {
                foreach ($locales->all() as $available) {
                    $query->orWhereMatches($where, "name->{$available->value}");
                }

                $query->orWhereMatches($where, 'slug');
            })
            ->tap(fn ($brands) => $query->orderByMatching($brands, "name->{$locale->value}"))
            ->orderBy('id')
            ->limit($query->limit)
            ->get()
            ->map(function (Brand $brand) use ($query, $locale): SearchResultData {
                $name = $brand->name->get($locale->value);

                return new SearchResultData(
                    $brand->id,
                    $name,
                    route('larasell.admin.brands.show', $brand),
                    $query->matches($name) || ! $query->matches($brand->slug) ? null : new SearchMatchData(__('Slug'), $brand->slug),
                );
            });

        return response()->json(['results' => $results->values()]);
    }
}
