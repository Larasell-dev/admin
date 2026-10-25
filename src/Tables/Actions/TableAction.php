<?php

namespace Larasell\Admin\Tables\Actions;

use Illuminate\Support\Str;
use JsonSerializable;
use Larasell\Admin\Enums\Icon;
use Larasell\Admin\Exceptions\InvalidTableAction;
use LogicException;

/**
 * An action for the whole table rather than one row, such as a link to add a
 * record. Tables show these in their header, or in their empty state.
 *
 * It only links for now. Its JSON names the request method, so actions that
 * run a handler can be added later without changing what links send.
 *
 * It shares no code with the row Action on purpose, so the two can evolve
 * independently.
 */
final class TableAction implements JsonSerializable
{
    private ?string $url = null;

    private ?Icon $icon = null;

    /** @var array<int, string> The configuration methods called so far, which may each be called only once. */
    private array $configured = [];

    private function __construct(private string $label) {}

    public static function make(string $label): self
    {
        return new self($label);
    }

    public function to(string $url): self
    {
        $this->configureOnce('to() or route()');
        $this->url = $url;

        return $this;
    }

    /**
     * @param  array<array-key, mixed>  $parameters
     */
    public function route(string $name, array $parameters = []): self
    {
        return $this->to(route($name, $parameters));
    }

    /**
     * Show an icon before the label: an Icon case or its Remix icon name, e.g. "add".
     */
    public function icon(Icon|string $icon): self
    {
        $this->configureOnce('icon()');
        $this->icon = is_string($icon) ? $this->resolveIcon($icon) : $icon;

        return $this;
    }

    public function name(): string
    {
        return Str::slug($this->label);
    }

    private function configureOnce(string $method): void
    {
        if (in_array($method, $this->configured, true)) {
            throw InvalidTableAction::configuredTwice($this->name(), $method);
        }

        $this->configured[] = $method;
    }

    private function resolveIcon(string $icon): Icon
    {
        if (Str::trim($icon) === '') {
            throw InvalidTableAction::emptyIcon($this->name());
        }

        // Checked before the lookup: "add-line" is not a case either, but this error says how to fix it.
        if (Str::endsWith($icon, ['-line', '-fill'])) {
            throw InvalidTableAction::iconStyleVariant($this->name(), $icon);
        }

        return Icon::tryFrom($icon) ?? throw InvalidTableAction::unknownIcon($this->name(), $icon);
    }

    /**
     * The icon is sent as its case name, which @larasell-dev/ui/icons exports with a Fill or Line suffix.
     *
     * @return array{label: string, method: 'get', url: string, icon: ?string}
     */
    public function jsonSerialize(): array
    {
        return [
            'label' => $this->label,
            'method' => 'get',
            'url' => $this->url ?? throw new LogicException("Action [{$this->name()}] needs a link: call to() or route()."),
            'icon' => $this->icon?->name,
        ];
    }
}
