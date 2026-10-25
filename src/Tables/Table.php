<?php

namespace Larasell\Admin\Tables;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use JsonException;
use JsonSerializable;
use Larasell\Admin\Enums\Icon;
use Larasell\Admin\Exceptions\InvalidTableColumn;
use Larasell\Admin\Exceptions\InvalidTableEmptyState;
use Larasell\Admin\Tables\Actions\Action;
use Larasell\Admin\Tables\Actions\TableAction;
use Larasell\Admin\Tables\Attributes\Remember;
use Larasell\Admin\Tables\Attributes\UseModel;
use Larasell\Admin\Tables\Columns\BadgeColumn;
use Larasell\Admin\Tables\Columns\Column;
use LogicException;
use ReflectionClass;

/**
 * To link each row somewhere, define a public rowLink() method that takes a
 * record of the table's model and returns its URL, or null to leave that row
 * unlinked. It is not declared here so it can type its parameter as the model:
 *
 *     public function rowLink(ProductReview $productReview): ?string
 *
 * A table may take arguments in its constructor, e.g. the record whose related
 * records it lists. Mark each with #[Remember] when the table has actions that
 * run a handler, so those can make the same table again.
 */
abstract class Table implements JsonSerializable
{
    /** @var class-string<Model>|null */
    protected ?string $model = null;

    /** The page sizes a visitor can choose from; the first one is the default. */
    private const array PAGE_SIZES = [25, 50, 100];

    /** @var array<class-string, class-string<Model>|false> */
    protected static array $cachedModelAttributes = [];

    /** The namespaces of the panel's own tables, by the prefix their slugs start with. */
    private const array PANEL_NAMESPACES = [
        'larasell:' => 'Larasell\\Admin\\Tables\\Panel\\',
        'larasell-product:' => 'Larasell\\Admin\\Tables\\Product\\',
    ];

    public static function make(mixed ...$arguments): static
    {
        // @phpstan-ignore new.static (subclasses define their own constructor arguments)
        return new static(...$arguments);
    }

    /**
     * Makes the table again from the state its action urls carry, or returns null when
     * the state is missing, altered, or names a record that no longer exists.
     */
    public static function fromState(?string $state): ?static
    {
        $parameters = (new ReflectionClass(static::class))->getConstructor()?->getParameters() ?? [];

        if ($parameters === []) {
            return static::make();
        }

        try {
            $values = json_decode(Crypt::decryptString((string) $state), true, flags: JSON_THROW_ON_ERROR);
        } catch (DecryptException|JsonException) {
            return null;
        }

        $arguments = [];

        foreach ($parameters as $parameter) {
            if (! is_array($values) || ! is_array($value = $values[$parameter->getName()] ?? null)) {
                return null;
            }

            if (isset($value['model'])) {
                $value = $value['model']::query()->find($value['key']);

                if ($value === null) {
                    return null;
                }
            } else {
                $value = $value['value'];
            }

            $arguments[$parameter->getName()] = $value;
        }

        return static::make(...$arguments);
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

    /**
     * Actions for the whole table, shown in its header, e.g. a link to add a record.
     * They are hidden while the table shows its empty state, which takes its own
     * actions; return the same ones from emptyState() to offer them there too.
     *
     * @return array<int, TableAction>
     */
    public function headerActions(): array
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
        // Can't clash with an app table, since a kebab-cased class name never contains a colon.
        foreach (self::PANEL_NAMESPACES as $prefix => $namespace) {
            if (str_starts_with(static::class, $namespace)) {
                return $prefix.self::path(Str::after(static::class, $namespace));
            }
        }

        $namespace = trim((string) config('larasell.admin.panel.tables.namespace'), '\\').'\\';

        if (! str_starts_with(static::class, $namespace)) {
            return null;
        }

        return self::path(Str::after(static::class, $namespace));
    }

    private static function path(string $class): string
    {
        return implode('.', array_map(fn (string $segment): string => Str::kebab($segment), explode('\\', $class)));
    }

    /**
     * @return class-string<Table>|null
     */
    public static function fromSlug(string $slug): ?string
    {
        $namespace = trim((string) config('larasell.admin.panel.tables.namespace'), '\\').'\\';
        $path = $slug;

        foreach (self::PANEL_NAMESPACES as $prefix => $panelNamespace) {
            if (str_starts_with($slug, $prefix)) {
                [$namespace, $path] = [$panelNamespace, Str::after($slug, $prefix)];
            }
        }

        $class = $namespace.implode('\\', array_map(fn (string $segment): string => Str::studly($segment), explode('.', $path)));

        if (! class_exists($class) || ! is_subclass_of($class, self::class) || $class::slug() !== $slug) {
            return null;
        }

        return $class;
    }

    public function actionUrl(Action $action, Model $record): string
    {
        $state = $this->state();

        $slug = static::slug() ?? throw new LogicException(
            static::class.' must live in the '.config('larasell.admin.panel.tables.namespace').' namespace to run actions.',
        );

        return route('larasell.admin.tables.actions', [
            'table' => $slug,
            'record' => $record->getKey(),
            'action' => $action->name(),
            // Encrypted, so the arguments stay hidden and can't be changed.
            ...$state === [] ? [] : ['state' => Crypt::encryptString(json_encode($state, JSON_THROW_ON_ERROR))],
        ]);
    }

    /**
     * The constructor arguments, read from the properties they promote, by parameter name.
     *
     * @return array<string, array{model: class-string<Model>, key: mixed}|array{value: mixed}>
     */
    private function state(): array
    {
        $parameters = (new ReflectionClass(static::class))->getConstructor()?->getParameters() ?? [];
        $state = [];

        foreach ($parameters as $parameter) {
            if ($parameter->getAttributes(Remember::class) === [] || ! $parameter->isPromoted()) {
                throw new LogicException(static::class." must mark its constructor argument [\${$parameter->getName()}] as a promoted property with #[Remember] to run actions.");
            }

            $value = $this->{$parameter->getName()};

            $state[$parameter->getName()] = $value instanceof Model
                ? ['model' => $value::class, 'key' => $value->getKey()]
                : ['value' => $value];
        }

        return $state;
    }

    /**
     * What to show when the table has no rows: a 'title', and optionally a
     * 'description', an 'icon' (an Icon case or its Remix icon name), and
     * 'actions' (a list of TableAction that resolve it, e.g. adding a record).
     * Return null for the default empty state.
     *
     * @return array<string, mixed>|null
     */
    public function emptyState(): ?array
    {
        return null;
    }

    /**
     * @return array{title: string, description: ?string, icon: ?string, actions: array<int, TableAction>}|null
     */
    protected function resolveEmptyState(): ?array
    {
        $state = $this->emptyState();

        if ($state === null) {
            return null;
        }

        foreach (array_keys($state) as $key) {
            if (! in_array($key, ['title', 'description', 'icon', 'actions'], true)) {
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

        $actions = $state['actions'] ?? [];
        if (! is_array($actions) || ! array_is_list($actions) || array_filter($actions, fn (mixed $action): bool => ! $action instanceof TableAction) !== []) {
            throw InvalidTableEmptyState::invalidValue(static::class, 'actions', 'a list of TableAction');
        }

        return [
            'title' => $title,
            'description' => $description,
            'icon' => $this->resolveEmptyStateIcon($state['icon'] ?? null),
            'actions' => $actions,
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
        return Str::ucfirst(Str::lower(Str::headline(Str::replaceEnd('Table', '', class_basename(static::class)))));
    }

    /**
     * The records the table lists: a model class, or a query or relationship
     * to list only some records, e.g. Book::where('published', true). Row
     * actions only run on records this returns. Defaults to the UseModel
     * attribute, the $model property, or the model guessModelName() names.
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

        $guess = self::guessModelName(static::class);

        if ($guess !== null && is_subclass_of($guess, Model::class)) {
            return $guess;
        }

        throw new LogicException(static::class.' must define a model with the UseModel attribute or a $model property'.($guess !== null ? ", or there must be a [{$guess}] model." : '.'));
    }

    /**
     * The model the given table class lists by convention, like Laravel guesses a model's resource:
     * App\Tables\Shop\ProductReview (or ProductReviewTable) lists App\Models\Shop\ProductReview.
     * The class it names may not exist.
     */
    public static function guessModelName(string $table): ?string
    {
        if (! Str::contains($table, '\\Tables\\')) {
            return null;
        }

        $relativeClass = Str::after($table, '\\Tables\\');
        $basename = Str::replaceEnd('Table', '', class_basename($table));
        $relativeNamespace = Str::contains($relativeClass, '\\') ? Str::beforeLast($relativeClass, '\\').'\\' : '';

        return Str::before($table, '\\Tables\\').'\\Models\\'.$relativeNamespace.$basename;
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
     * @return array{title: string, emptyState: array{title: string, description: ?string, icon: ?string, actions: array<int, TableAction>}|null, headerActions: array<int, TableAction>, columns: array<int, Column>, rows: array<int, array<string, mixed>>, pagination: array{page: int, pageSize: int, total: int}}
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
        foreach (array_intersect($columnNames, ['_actions', '_link', '_images', '_variants']) as $reserved) {
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
            $images = [];
            $variants = [];

            foreach ($columns as $column) {
                $row[$column->name()] = $column->value($record);

                if ($column->hasImage()) {
                    $images[$column->name()] = $column->imageFor($record);
                }

                if ($column instanceof BadgeColumn) {
                    $variants[$column->name()] = $column->variantFor($record);
                }
            }

            $row['_actions'] = array_map(fn (Action $action): array => $action->toRow($this, $record), $actions);
            $row['_link'] = method_exists($this, 'rowLink') ? $this->rowLink($record) : null;
            // An object, so a table without images still sends {} rather than [].
            $row['_images'] = (object) $images;
            $row['_variants'] = (object) $variants;

            return $row;
        });

        return [
            'title' => $this->title(),
            'emptyState' => $this->resolveEmptyState(),
            'headerActions' => $this->headerActions(),
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
