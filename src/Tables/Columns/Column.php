<?php

namespace Larasell\Admin\Tables\Columns;

use Closure;
use Illuminate\Database\Eloquent\MissingAttributeException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use JsonSerializable;

abstract class Column implements JsonSerializable
{
    /** @var (Closure(mixed, Model): mixed)|null */
    protected ?Closure $map = null;

    final public function __construct(
        protected string $name,
        protected ?string $label = null,
    ) {}

    public static function make(string $name, ?string $label = null): static
    {
        return new static($name, $label);
    }

    abstract public function type(): string;

    public function name(): string
    {
        return $this->name;
    }

    public function label(): string
    {
        return $this->label ?? Str::ucfirst(str_replace('_', ' ', Str::snake($this->name)));
    }

    /**
     * Transform the column's value before it is shown. The callback receives
     * the attribute's (cast) value, or null when the model has no attribute
     * by the column's name, and the record, so it can compute a column:
     *
     *     TextColumn::make('name')->map(fn ($value, Contact $contact) => "{$contact->first_name} {$contact->last_name}")
     *
     * @param  Closure(mixed, Model): mixed  $callback
     */
    public function map(Closure $callback): static
    {
        $this->map = $callback;

        return $this;
    }

    public function value(Model $record): mixed
    {
        if ($this->map === null) {
            return $record->getAttribute($this->name);
        }

        try {
            $value = $record->getAttribute($this->name);
        } catch (MissingAttributeException) {
            // A computed column need not be an attribute, even on strict models.
            $value = null;
        }

        return ($this->map)($value, $record);
    }

    /**
     * @return array{name: string, label: string, type: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name(),
            'label' => $this->label(),
            'type' => $this->type(),
        ];
    }
}
