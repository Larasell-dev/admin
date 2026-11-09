<?php

namespace Larasell\Admin\Platform\Settings;

use Larasell\Admin\Platform\Settings\Data\SettingsPageData;
use Larasell\Admin\Platform\Settings\Exceptions\InvalidSettingsPage;
use Larasell\Admin\Platform\Support\Assert;

/**
 * The pages on the settings index, which the Settings navigation item opens, and which the
 * command menu offers under "Go to". The panel's own are "team", "api-tokens" and "audit-log".
 *
 * @public Plugins add pages from a service provider's boot(), through the Settings facade:
 *     Settings::add('acme-wishlists')->label(...)->icon(Icon::Heart)->route('acme.wishlists.settings').
 */
class Settings
{
    /** @var array<string, SettingsPage> In registration order, by id */
    private array $pages = [];

    public function add(string $id): SettingsPage
    {
        $page = new SettingsPage($id);

        // In production a broken page is left off the index, which works without it.
        if (! Assert::that(preg_match('/^[a-z0-9][a-z0-9-]*$/', $id) === 1, fn () => InvalidSettingsPage::invalidId($id))) {
            return $page;
        }

        if (! Assert::that(! isset($this->pages[$id]), fn () => InvalidSettingsPage::duplicateId($id))) {
            return $page;
        }

        return $this->pages[$id] = $page;
    }

    /**
     * The complete pages in order: in registration order, with each placed page moved next to
     * its target. A page whose target isn't there, e.g. a turned off module's, goes last.
     *
     * @return list<SettingsPageData>
     */
    public function data(): array
    {
        $pages = $this->pages;

        $ordered = array_values(array_filter($pages, fn (SettingsPage $page): bool => $page->before === null && $page->after === null));
        $pending = array_values(array_diff_key($pages, array_flip(array_map(fn (SettingsPage $page): string => $page->id, $ordered))));

        // Each pass places the pages whose target is placed; a pass without progress leaves the rest last.
        while ($pending !== []) {
            $left = [];

            foreach ($pending as $page) {
                $index = array_search($page->before ?? $page->after, array_map(fn (SettingsPage $placed): string => $placed->id, $ordered), true);

                if ($index === false) {
                    $left[] = $page;

                    continue;
                }

                array_splice($ordered, $page->before !== null ? $index : $index + 1, 0, [$page]);
            }

            if (count($left) === count($pending)) {
                array_push($ordered, ...$left);
                break;
            }

            $pending = $left;
        }

        return array_values(array_filter(array_map(fn (SettingsPage $page): ?SettingsPageData => $this->toData($page), $ordered)));
    }

    /**
     * The page as its card shows it, or null when it misses a field the card needs.
     */
    private function toData(SettingsPage $page): ?SettingsPageData
    {
        foreach (['label' => $page->label, 'icon' => $page->icon, 'route' => $page->route] as $field => $value) {
            if (! Assert::that($value !== null, fn () => InvalidSettingsPage::incomplete($page->id, $field))) {
                return null;
            }
        }

        if ($page->label === null || $page->icon === null || $page->route === null) {
            return null;
        }

        // Registered while the app boots, so translated now, in the request's language.
        $description = $page->description === null ? null : __($page->description);

        return new SettingsPageData(
            id: $page->id,
            label: __($page->label),
            // A lang file's group is an array; a description is text, so it stays as it is then.
            description: is_array($description) ? $page->description : $description,
            icon: $page->icon->name,
            url: route($page->route),
        );
    }
}
