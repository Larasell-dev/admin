<?php

namespace Larasell\Admin\Enums\Concerns;

trait HasLabel
{
    /**
     * Labels keyed by case value, e.g. for selects.
     *
     * @return array<int|string, string>
     */
    public static function all(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])->all();
    }

    abstract public function label(): string;
}
