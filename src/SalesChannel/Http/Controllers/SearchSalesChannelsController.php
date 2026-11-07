<?php

namespace Larasell\Admin\SalesChannel\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Larasell\Admin\Platform\ContentLocale\CurrentContentLocale;
use Larasell\Admin\Platform\Search\Data\SearchResultData;
use Larasell\Admin\Platform\Search\Http\Requests\SearchRequest;
use Larasell\Admin\SalesChannel\Search\SalesChannelSearch;
use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\SalesChannels\Models\SalesChannel;

/**
 * The command menu's sales channel search, by name.
 */
class SearchSalesChannelsController
{
    public function __invoke(SearchRequest $request, SalesChannelSearch $search, #[CurrentContentLocale] Locale $locale): JsonResponse
    {
        $results = $search->search($request->toQuery($locale))->map(fn (SalesChannel $channel): SearchResultData => new SearchResultData(
            $channel->id,
            $channel->name,
            route('larasell.admin.sales-channels.edit', $channel),
            null,
        ));

        return response()->json(['results' => $results->values()]);
    }
}
