<?php

namespace Larasell\Admin\Tables;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use JsonSerializable;
use Larasell\Admin\Enums\Icon;
use Larasell\Admin\Exceptions\InvalidTableColumn;
use Larasell\Admin\Exceptions\InvalidTableEmptyState;
use Larasell\Admin\Tables\Actions\Action;
use Larasell\Admin\Tables\Attributes\UseModel;
use Larasell\Admin\Tables\Columns\Column;
use LogicException;
use ReflectionClass;

/**
 * To link each row somewhere, define a public rowLink() method that takes a
 * record of the table's model and returns its URL, or null to leave that row
 * unlinked. It is not declared here so it can type its parameter as the model:
 *
 *     public function rowLink(ProductReview $productReview): ?string
 */
abstract class Table implements JsonSerializable
{
    /** @var class-string<Model>|null */
    protected ?string $model = null;

    /** The page sizes a visitor can choose from; the first one is the default. */
    private const array PAGE_SIZES = [25, 50, 100];

    /** @var array<class-string, class-string<Model>|false> */
    protected static array $cachedModelAttributes = [];

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
     * 'description' and an 'icon' (an Icon case or its Remix icon name).
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
        if ($icon === null || $icon instanceof Icon) {
            return $icon?->name;
        }

        if (! is_string($icon)) {
            throw InvalidTableEmptyState::invalidValue(static::class, 'icon', 'an Icon case or a Remix icon name');
        }

        if (Str::trim($icon) === '') {
            throw InvalidTableEmptyState::emptyIcon(static::class);
        }

        // Checked before the lookup: "home-line" is not a case either, but this error says how to fix it.
        if (Str::endsWith($icon, ['-line', '-fill'])) {
            throw InvalidTableEmptyState::iconStyleVariant(static::class, $icon);
        }

        return (Icon::tryFrom($icon) ?? throw InvalidTableEmptyState::unknownIcon(static::class, $icon))->name;
    }

    public function title(): string
    {
        return Str::ucfirst(Str::lower(Str::headline(class_basename(static::class))));
    }

    /**
     * The records the table lists: a model class, or a query or relationship
     * to list only some records, e.g. Book::where('published', true). Row
     * actions only run on records this returns. Defaults to the UseModel
     * attribute or the $model property.
     *
     * @return class-string<Model>|Builder<covariant Model>|Relation<covariant Model, covariant Model, mixed>
     */
    protected function model(): Builder|Relation|string
    {
        if (! array_key_exists(static::class, static::$cachedModelAttributes)) {
            $attribute = (new ReflectionClass(static::class))->getAttributes(UseModel::class);

            static::$cachedModelAttributes[static::class] = $attribute !== []
                ? $attribute[0]->newInstance()->class
                : false;
        }

        if (static::$cachedModelAttributes[static::class]) {
            return static::$cachedModelAttributes[static::class];
        }

        if ($this->model !== null) {
            return $this->model;
        }

        throw new LogicException(static::class.' must define a model with the UseModel attribute or a $model property.');
    }

    /**
     * @return Builder<covariant Model>|Relation<covariant Model, covariant Model, mixed>
     */
    private function query(): Builder|Relation
    {
        $model = $this->model();

        return is_string($model) ? $model::query() : $model;
    }

    /**
     * @return array{title: string, emptyState: array{title: string, description: ?string, icon: ?string}|null, columns: array<int, Column>, rows: array<int, array<string, mixed>>, pagination: array{page: int, pageSize: int, total: int}}
     */
    public function jsonSerialize(): array
    {
        $columns = $this->columns();
        $actions = $this->actions();

        foreach ($actions as $action) {
            $action->assertConfigured();
        }

        $columnNames = array_map(fn (Column $column): string => $column->name(), $columns);
        foreach (array_unique(array_diff_assoc($columnNames, array_unique($columnNames))) as $duplicate) {
            throw InvalidTableColumn::duplicateName(static::class, $duplicate);
        }
        foreach (array_intersect($columnNames, ['_actions', '_link']) as $reserved) {
            throw InvalidTableColumn::reservedName(static::class, $reserved);
        }

        $names = array_map(fn (Action $action): string => $action->name(), $actions);
        foreach (array_unique(array_diff_assoc($names, array_unique($names))) as $duplicate) {
            throw new LogicException(static::class." defines more than one action named [{$duplicate}].");
        }

        $pageSize = request()->integer('per_page');
        if (! in_array($pageSize, self::PAGE_SIZES, true)) {
            $pageSize = self::PAGE_SIZES[0];
        }

        // Ordered by key so that every row shows up on exactly one page.
        $query = $this->query();
        $query->orderBy($query->getModel()->getQualifiedKeyName());
        $paginator = $query->paginate($pageSize);

        // Past the last page, e.g. after deleting its only row, show the last page instead.
        if ($paginator->isEmpty() && $paginator->currentPage() > $paginator->lastPage()) {
            $paginator = $query->paginate($pageSize, page: $paginator->lastPage());
        }

        $rows = $paginator->getCollection()->map(function (Model $record) use ($columns, $actions): array {
            $row = ['id' => $record->getKey()];

            foreach ($columns as $column) {
                $row[$column->name()] = $column->value($record);
            }

            $row['_actions'] = array_map(fn (Action $action): array => $action->toRow($this, $record), $actions);
            $row['_link'] = method_exists($this, 'rowLink') ? $this->rowLink($record) : null;

            return $row;
        });

        return [
            'title' => $this->title(),
            'emptyState' => $this->resolveEmptyState(),
            'columns' => $columns,
            'rows' => $rows->all(),
            'pagination' => [
                'page' => $paginator->currentPage(),
                'pageSize' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }
}
