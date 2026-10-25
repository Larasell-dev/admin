<?php

namespace Larasell\Admin\Tables\Columns;

use BackedEnum;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Larasell\Admin\Enums\Table\Variant;
use Larasell\Admin\Exceptions\InvalidTableColumn;
use Larasell\Admin\Tables\Columns\Concerns\HasTextValue;

/**
 * Shows the column's value as text in a badge, coloured by variant().
 */
class BadgeColumn extends Column
{
    use HasTextValue;

    /**
     * The variant of each value, or a callback that takes the value and the record and returns one.
     *
     * @var array<array-key, Variant>|Closure|null
     */
    protected array|Closure|null $variant = null;

    public function type(): string
    {
        return 'badge';
    }

    /**
     * Colour the badge by the column's value: a map from values to variants,
     * or a callback that receives the value and the record and returns one.
     * A variant is a Variant case or its name, e.g. 'success'. Values without
     * a variant, and a callback returning null, show a neutral badge:
     *
     *     BadgeColumn::make('status')->variant(['approved' => Variant::Success, 'rejected' => Variant::Danger])
     *     BadgeColumn::make('status')->variant(fn (string $status, Order $order) => $order->isOverdue() ? Variant::Danger : null)
     *
     * The value is the attribute's (cast) value, before map(), so the badge's
     * text may differ from what picks its colour. Map an enum cast attribute by
     * its backing value.
     *
     * @template TModel of Model
     *
     * @param  array<array-key, Variant|string>|Closure(mixed, TModel): (Variant|string|null)  $variant
     */
    public function variant(array|Closure $variant): static
    {
        if ($this->variant !== null) {
            throw InvalidTableColumn::configuredTwice($this->name, 'variant()');
        }

        // Checked here, so a typo fails where the column is defined rather than only for rows with that value.
        $this->variant = $variant instanceof Closure
            ? $variant
            : array_map(fn (mixed $value): Variant => $this->resolveVariant($value) ?? Variant::Neutral, $variant);

        return $this;
    }

    public function variantFor(Model $record): Variant
    {
        if ($this->variant === null) {
            return Variant::Neutral;
        }

        $value = $this->attribute($record);

        if ($this->variant instanceof Closure) {
            $variant = ($this->variant)($value, $record);
            $this->assertUnmodified($record, 'variant()');

            return $this->resolveVariant($variant) ?? Variant::Neutral;
        }

        $key = $value instanceof BackedEnum ? $value->value : $value;

        if (is_int($key)) {
            return $this->variant[$key] ?? Variant::Neutral;
        }

        if (is_string($key)) {
            return $this->variant[$key] ?? Variant::Neutral;
        }

        return Variant::Neutral;
    }

    private function resolveVariant(mixed $variant): ?Variant
    {
        if ($variant === null || $variant instanceof Variant) {
            return $variant;
        }

        if (is_string($variant)) {
            return Variant::tryFrom($variant) ?? throw InvalidTableColumn::invalidVariant($this->name, "\"{$variant}\"");
        }

        throw InvalidTableColumn::invalidVariant($this->name, get_debug_type($variant));
    }
}
