<?php

namespace Larasell\Admin\Tables;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use JsonSerializable;
use Larasell\Admin\Tables\Columns\Column;
use LogicException;

abstract class Table implements JsonSerializable
{
    /** @var class-string<Model>|null */
    protected ?string $model = null;

    final public function __construct() {}

    public static function make(): static
    {
        return new static;
    }

    /**
     * @return array<int, Column>
     */
    abstract public function columns(): array;

    public function title(): string
    {
        return Str::ucfirst(Str::lower(Str::headline(class_basename(static::class))));
    }

    /**
     * @return Builder<Model>
     */
    protected function query(): Builder
    {
        if ($this->model === null) {
            throw new LogicException(static::class.' must define a $model or override query().');
        }

        return $this->model::query();
    }

    /**
     * @return array{title: string, columns: array<int, Column>, rows: array<int, array<string, mixed>>}
     */
    public function jsonSerialize(): array
    {
        $columns = $this->columns();

        $rows = $this->query()->get()->map(function (Model $record) use ($columns): array {
            $row = ['id' => $record->getKey()];

            foreach ($columns as $column) {
                $row[$column->name()] = $column->value($record);
            }

            return $row;
        });

        return [
            'title' => $this->title(),
            'columns' => $columns,
            'rows' => $rows->all(),
        ];
    }
}
