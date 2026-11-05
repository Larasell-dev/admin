<?php

namespace Larasell\Admin\Platform\Search;

use Illuminate\Contracts\Database\Query\ConditionExpression;
use Illuminate\Database\Grammar;
use Illuminate\Database\Query\Grammars\SQLiteGrammar;

/**
 * The SQL condition that columns contain a text, ignoring case and spaces. Several columns, e.g. a
 * first and a last name, are joined and match as one. See SearchQuery::matches().
 */
final readonly class TextMatch implements ConditionExpression
{
    /**
     * @param  non-empty-list<string>  $columns
     */
    public function __construct(private array $columns, private string $text) {}

    public function getValue(Grammar $grammar): string
    {
        // Never null, e.g. for a locale a name has no translation in, so the condition is never null either.
        $wrapped = array_map(fn (string $column): string => "coalesce({$grammar->wrap($column)}, '')", $this->columns);
        $joined = count($wrapped) === 1
            ? $wrapped[0]
            : ($grammar instanceof SQLiteGrammar ? implode(' || ', $wrapped) : 'concat('.implode(', ', $wrapped).')');

        // Takes % and _ in the text literally.
        $text = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], SearchQuery::normalize($this->text));

        return "replace(lower({$joined}), ' ', '') like {$grammar->escape("%{$text}%")} escape '!'";
    }
}
