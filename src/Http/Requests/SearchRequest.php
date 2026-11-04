<?php

namespace Larasell\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Larasell\Admin\Search\SearchQuery;
use Larasell\Larasell\Enums\Locale;

/**
 * A search from the command menu. It's only text; the menu has no other filters.
 */
class SearchRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'max:200'],
        ];
    }

    public function toQuery(Locale $locale): SearchQuery
    {
        return new SearchQuery($this->string('q')->trim()->value(), $locale);
    }
}
