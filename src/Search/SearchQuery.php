<?php

namespace Larasell\Admin\Search;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Larasell\Larasell\Enums\Locale;

/**
 * What an admin searched for in the command menu, for a search such as ProductSearch.
 *
 * @public Searches bound in the container receive it; decorators can pass on a copy with more criteria.
 */
final readonly class SearchQuery
{
    /**
     * @param  string  $text  What the admin typed.
     * @param  Locale  $locale  The content locale, whose matches rank first and which results are shown in.
     * @param  int  $limit  At most this many results.
     * @param  array<string, mixed>  $criteria  More conditions, which only decorators add, e.g. from parsing the text.
     */
    public function __construct(
        public string $text,
        public Locale $locale,
        public int $limit = 10,
        public array $criteria = [],
    ) {}

    /**
     * Whether the value contains the text, ignoring case and spaces like the command menu's own
     * search, so "oxfordshirt" finds "Oxford Shirt". It's how the database searches match.
     */
    public function matches(string $value): bool
    {
        return str_contains(self::normalize($value), self::normalize($this->text));
    }

    /**
     * Adds an or-condition that the column matches like matches() does. Several columns, e.g. a
     * first and a last name, are joined and match as one.
     *
     * @param  Builder  $query
     */
    public function orWhereMatches($query, string $column, string ...$more): void
    {
        $query->orWhere(new TextMatch([$column, ...array_values($more)], $this->text));
    }

    /**
     * Ranks the rows whose column matches before the others.
     *
     * @param  Builder  $query
     */
    public function orderByMatching($query, string $column): void
    {
        $query->orderByDesc(new TextMatch([$column], $this->text));
    }

    /**
     * In lower case and without spaces, which is how the text and the values are compared.
     *
     * @internal
     */
    public static function normalize(string $value): string
    {
        return str_replace(' ', '', mb_strtolower($value));
    }
}
