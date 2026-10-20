<?php

namespace Larasell\Admin\Tables\Actions;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Larasell\Admin\Enums\Icon;
use Larasell\Admin\Exceptions\InvalidTableAction;
use Larasell\Admin\Tables\Table;
use LogicException;

class Action
{
    protected string|Closure|null $url = null;

    protected bool $confirms = false;

    protected bool $destructive = false;

    protected bool|Closure $disabled = false;

    protected ?Icon $icon = null;

    /**
     * @var array{title: ?string, message: ?string, confirmButton: ?string, cancelButton: ?string, confirmationText: string|Closure|null}
     */
    protected array $confirmation = [
        'title' => null,
        'message' => null,
        'confirmButton' => null,
        'cancelButton' => null,
        'confirmationText' => null,
    ];

    final public function __construct(
        protected string $label,
        protected ?Closure $handle = null,
    ) {}

    public static function make(string $label, ?Closure $handle = null): static
    {
        return new static($label, $handle);
    }

    /**
     * Link to a URL, or to a URL built from the row's record.
     */
    public function to(string|Closure $url): static
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Link to a named route. Without parameters, the row's record is the route parameter.
     *
     * @param  array<array-key, mixed>  $parameters
     */
    public function route(string $name, array $parameters = []): static
    {
        return $this->to(fn (Model $record): string => route($name, $parameters === [] ? $record : $parameters));
    }

    public function confirm(
        ?string $title = null,
        ?string $message = null,
        ?string $confirmButton = null,
        ?string $cancelButton = null,
        string|Closure|null $confirmationText = null,
    ): static {
        $this->confirms = true;
        $this->confirmation = compact('title', 'message', 'confirmButton', 'cancelButton', 'confirmationText');

        return $this;
    }

    /**
     * Mark the action as one that destroys or irreversibly changes data, so it stands out.
     */
    public function destructive(bool $condition = true): static
    {
        $this->destructive = $condition;

        return $this;
    }

    /**
     * Disable the action, for every row or for the rows where the closure returns true.
     */
    public function disabled(bool|Closure $condition = true): static
    {
        $this->disabled = $condition;

        return $this;
    }

    /**
     * Show an icon before the label: an Icon case or its Remix icon name, e.g. "delete-bin".
     */
    public function icon(Icon|string $icon): static
    {
        $this->icon = is_string($icon) ? $this->resolveIcon($icon) : $icon;

        return $this;
    }

    protected function resolveIcon(string $icon): Icon
    {
        if (Str::trim($icon) === '') {
            throw InvalidTableAction::emptyIcon($this->name());
        }

        // Checked before the lookup: "home-line" is not a case either, but this error says how to fix it.
        if (Str::endsWith($icon, ['-line', '-fill'])) {
            throw InvalidTableAction::iconStyleVariant($this->name(), $icon);
        }

        return Icon::tryFrom($icon) ?? throw InvalidTableAction::unknownIcon($this->name(), $icon);
    }

    public function isDisabledFor(Model $record): bool
    {
        return $this->disabled instanceof Closure ? (bool) ($this->disabled)($record) : $this->disabled;
    }

    public function name(): string
    {
        return Str::slug($this->label);
    }

    public function label(): string
    {
        return $this->label;
    }

    public function assertConfigured(): void
    {
        if ($this->handle === null && $this->url === null) {
            throw new LogicException("Action [{$this->name()}] needs a handler or a link: pass a handle closure to Action::make(), or call to() or route().");
        }
    }

    public function runs(): bool
    {
        return $this->handle !== null;
    }

    public function run(Model $record): void
    {
        if ($this->handle === null) {
            throw new LogicException("Action [{$this->name()}] has no handler to run.");
        }

        ($this->handle)($record);
    }

    /**
     * The text an admin must type to confirm the action on the record, if any.
     */
    public function confirmationTextFor(Model $record): ?string
    {
        $text = $this->confirmation['confirmationText'];

        return $text instanceof Closure ? (string) $text($record) : $text;
    }

    /**
     * The icon is sent as its case name, which @larasell-dev/ui/icons exports with a Fill or Line suffix.
     *
     * @return array{name: string, label: string, method: string, url: string, confirm: array<string, string|null>|null, destructive: bool, disabled: bool, icon: ?string}
     */
    public function toRow(Table $table, Model $record): array
    {
        return [
            'name' => $this->name(),
            'label' => $this->label,
            'method' => $this->runs() ? 'post' : 'get',
            'url' => $this->runs() ? $table->actionUrl($this, $record) : $this->urlFor($record),
            'confirm' => $this->confirms ? $this->confirmationFor($record) : null,
            'destructive' => $this->destructive,
            'disabled' => $this->isDisabledFor($record),
            'icon' => $this->icon?->name,
        ];
    }

    protected function urlFor(Model $record): string
    {
        return $this->url instanceof Closure ? (string) ($this->url)($record) : (string) $this->url;
    }

    /**
     * @return array{title: string, message: string, confirmButton: string, cancelButton: string, confirmationText: ?string}
     */
    protected function confirmationFor(Model $record): array
    {
        $verb = Str::lower($this->label);
        $model = Str::lower(Str::headline(class_basename($record)));

        return [
            'title' => $this->confirmation['title'] ?? Str::ucfirst("{$verb} {$model}"),
            'message' => $this->confirmation['message'] ?? "Are you sure you want to {$verb} this {$model}?",
            'confirmButton' => $this->confirmation['confirmButton'] ?? $this->label,
            'cancelButton' => $this->confirmation['cancelButton'] ?? 'Cancel',
            'confirmationText' => $this->confirmationTextFor($record),
        ];
    }
}
