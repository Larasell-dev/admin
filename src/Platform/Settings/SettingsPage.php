<?php

namespace Larasell\Admin\Platform\Settings;

use Larasell\Admin\Platform\Panel\Icon;

/**
 * A page on the settings index, built with Settings::add($id). It only says how the page is shown
 * and where it links to; the page's routes and controller are the module's or plugin's own.
 *
 * label(), icon() and route() are required. Without before() or after() the page goes last.
 *
 * @public Plugins add pages with Settings::add(), the facade of the Settings registry.
 */
final class SettingsPage
{
    public ?string $label = null;

    public ?string $description = null;

    public ?Icon $icon = null;

    public ?string $route = null;

    public ?string $before = null;

    public ?string $after = null;

    public function __construct(public readonly string $id) {}

    /** Marked with Translate::later(); translated when shown. */
    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    /** One line under the label on its card. Marked with Translate::later(). */
    public function description(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function icon(Icon $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    /** The name of the route the page links to, any route the app registers. */
    public function route(string $route): self
    {
        $this->route = $route;

        return $this;
    }

    /** Places the page before another one by its id, e.g. "api-tokens". */
    public function before(string $id): self
    {
        [$this->before, $this->after] = [$id, null];

        return $this;
    }

    /** Places the page after another one by its id, e.g. "audit-log". */
    public function after(string $id): self
    {
        [$this->before, $this->after] = [null, $id];

        return $this;
    }
}
