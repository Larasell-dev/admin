<?php

namespace Larasell\Admin\Tables\Actions\Concerns;

use Illuminate\Support\Str;
use Larasell\Admin\Enums\Icon;
use Larasell\Admin\Exceptions\InvalidTableAction;

/**
 * The configuration row actions and table actions share: an icon, and
 * configuration methods that may each be called only once.
 */
trait ConfiguresAction
{
    protected ?Icon $icon = null;

    /** @var array<int, string> The configuration methods called so far. */
    private array $configured = [];

    abstract public function name(): string;

    /**
     * Show an icon before the label: an Icon case or its Remix icon name, e.g. "delete-bin".
     */
    public function icon(Icon|string $icon): static
    {
        $this->configureOnce('icon()');
        $this->icon = is_string($icon) ? $this->resolveIcon($icon) : $icon;

        return $this;
    }

    private function configureOnce(string $method): void
    {
        if (in_array($method, $this->configured, true)) {
            throw InvalidTableAction::configuredTwice($this->name(), $method);
        }

        $this->configured[] = $method;
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
}
