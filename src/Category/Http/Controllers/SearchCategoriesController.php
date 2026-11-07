<?php

namespace Larasell\Admin\Category\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;
use Larasell\Admin\Category\Search\CategorySearch;
use Larasell\Admin\Platform\ContentLocale\CurrentContentLocale;
use Larasell\Admin\Platform\ContentLocale\Locales\AvailableLocales;
use Larasell\Admin\Platform\Search\Data\SearchMatchData;
use Larasell\Admin\Platform\Search\Data\SearchResultData;
use Larasell\Admin\Platform\Search\Http\Requests\SearchRequest;
use Larasell\Larasell\Catalog\Models\Category;
use Larasell\Larasell\Enums\Locale;

/**
 * The command menu's category search: each category's name in the content locale, and the name
 * that matched when it's another locale's.
 */
class SearchCategoriesController
{
    public function __invoke(
        SearchRequest $request,
        CategorySearch $search,
        AvailableLocales $locales,
        #[CurrentContentLocale] Locale $locale,
    ): JsonResponse {
        $query = $request->toQuery($locale);

        $results = $search->search($query)->map(function (Category $category) use ($query, $locale, $locales): SearchResultData {
            $label = $category->name->get($locale->value);
            $match = null;

            if (! $query->matches($label)) {
                foreach ($locales->all() as $other) {
                    $name = $category->name->all()[$other->value] ?? null;

                    if ($name !== null && $query->matches($name)) {
                        $match = new SearchMatchData(\Locale::getDisplayName($other->value, App::currentLocale()) ?: $other->value, $name);
                        break;
                    }
                }
            }

            return new SearchResultData($category->id, $label, route('larasell.admin.categories.show', $category), $match);
        });

        return response()->json(['results' => $results->values()]);
    }
}
