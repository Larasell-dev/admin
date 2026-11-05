<?php

namespace Larasell\Admin\Http\Controllers\Categories;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;
use Larasell\Admin\Category\Search\CategorySearch;
use Larasell\Admin\ContentLocale\AvailableLocales;
use Larasell\Admin\ContentLocale\CurrentContentLocale;
use Larasell\Admin\Data\ViewModel\Search\SearchMatchData;
use Larasell\Admin\Data\ViewModel\Search\SearchResultData;
use Larasell\Admin\Http\Requests\SearchRequest;
use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\Models\Category;

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
