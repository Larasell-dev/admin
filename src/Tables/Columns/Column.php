<?php

namespace Larasell\Admin\Tables\Columns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use JsonSerializable;

abstract class Column implements JsonSerializable
{
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

    public function value(Model $record): mixed
    {
        return $record->getAttribute($this->name);
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
