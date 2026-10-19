<?php

namespace Larasell\Admin\Tables;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use JsonSerializable;
use Larasell\Admin\Enums\NavIcon;
use Larasell\Admin\Exceptions\InvalidTableEmptyState;
use Larasell\Admin\Tables\Actions\Action;
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

    /**
     * @return array<int, Action>
     */
    public function actions(): array
    {
        return [];
    }

    public function action(string $name): ?Action
    {
        foreach ($this->actions() as $action) {
            if ($action->name() === $name) {
                return $action;
            }
        }

        return null;
    }

    public function findRecord(string $key): ?Model
    {
        return $this->query()->whereKey($key)->first();
    }

    /**
     * The table's path in the tables namespace, e.g. "shop.product-reviews" for
     * App\Tables\Shop\ProductReviews, or null when it lives outside that namespace.
     */
    public static function slug(): ?string
    {
        $namespace = trim((string) config('larasell-admin.tables.namespace'), '\\').'\\';

        if (! str_starts_with(static::class, $namespace)) {
            return null;
        }

        $segments = explode('\\', Str::after(static::class, $namespace));

        return implode('.', array_map(fn (string $segment): string => Str::kebab($segment), $segments));
    }

    /**
     * @return class-string<Table>|null
     */
    public static function fromSlug(string $slug): ?string
    {
        $namespace = trim((string) config('larasell-admin.tables.namespace'), '\\');
        $class = $namespace.'\\'.implode('\\', array_map(fn (string $segment): string => Str::studly($segment), explode('.', $slug)));

        if (! class_exists($class) || ! is_subclass_of($class, self::class) || $class::slug() !== $slug) {
            return null;
        }

        return $class;
    }

    public function actionUrl(Action $action, Model $record): string
    {
        $slug = static::slug() ?? throw new LogicException(
            static::class.' must live in the '.config('larasell-admin.tables.namespace').' namespace to run actions.',
        );

        return route('larasell.admin.tables.actions', [
            'table' => $slug,
            'record' => $record->getKey(),
            'action' => $action->name(),
        ]);
    }

    /**
     * What to show when the table has no rows: a 'title', and optionally a
     * 'description' and an 'icon' (a NavIcon case or its Remix icon name).
     * Return null for the default empty state.
     *
     * @return array<string, mixed>|null
     */
    public function emptyState(): ?array
    {
        return null;
    }

    /**
     * @return array{title: string, description: ?string, icon: ?string}|null
     */
    protected function resolveEmptyState(): ?array
    {
        $state = $this->emptyState();

        if ($state === null) {
            return null;
        }

        foreach (array_keys($state) as $key) {
            if (! in_array($key, ['title', 'description', 'icon'], true)) {
                throw InvalidTableEmptyState::unknownKey(static::class, (string) $key);
            }
        }

        $title = $state['title'] ?? null;
        if ($title !== null && ! is_string($title)) {
            throw InvalidTableEmptyState::invalidValue(static::class, 'title', 'a string');
        }
        if ($title === null || trim($title) === '') {
            throw InvalidTableEmptyState::missingTitle(static::class);
        }

        $description = $state['description'] ?? null;
        if ($description !== null && ! is_string($description)) {
            throw InvalidTableEmptyState::invalidValue(static::class, 'description', 'a string');
        }

        return [
            'title' => $title,
            'description' => $description,
            'icon' => $this->resolveEmptyStateIcon($state['icon'] ?? null),
        ];
    }

    protected function resolveEmptyStateIcon(mixed $icon): ?string
    {
        if ($icon === null || $icon instanceof NavIcon) {
            return $icon?->name;
        }

        if (! is_string($icon)) {
            throw InvalidTableEmptyState::invalidValue(static::class, 'icon', 'a NavIcon case or a Remix icon name');
        }

        if (str_ends_with($icon, '-line') || str_ends_with($icon, '-fill')) {
            throw InvalidTableEmptyState::iconStyleVariant(static::class, $icon);
        }

        return (NavIcon::tryFrom($icon) ?? throw InvalidTableEmptyState::unknownIcon(static::class, $icon))->name;
    }

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
     * @return array{title: string, emptyState: array{title: string, description: ?string, icon: ?string}|null, columns: array<int, Column>, rows: array<int, array<string, mixed>>}
     */
    public function jsonSerialize(): array
    {
        $columns = $this->columns();
        $actions = $this->actions();

        foreach ($actions as $action) {
            $action->assertConfigured();
        }

        $names = array_map(fn (Action $action): string => $action->name(), $actions);
        foreach (array_unique(array_diff_assoc($names, array_unique($names))) as $duplicate) {
            throw new LogicException(static::class." defines more than one action named [{$duplicate}].");
        }

        $rows = $this->query()->get()->map(function (Model $record) use ($columns, $actions): array {
            $row = ['id' => $record->getKey()];

            foreach ($columns as $column) {
                $row[$column->name()] = $column->value($record);
            }

            $row['_actions'] = array_map(fn (Action $action): array => $action->toRow($this, $record), $actions);

            return $row;
        });

        return [
            'title' => $this->title(),
            'emptyState' => $this->resolveEmptyState(),
            'columns' => $columns,
            'rows' => $rows->all(),
        ];
    }
}
