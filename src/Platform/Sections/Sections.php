<?php

namespace Larasell\Admin\Platform\Sections;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Larasell\Admin\Platform\Sections\Data\SectionData;
use Larasell\Admin\Platform\Sections\Exceptions\InvalidSection;

/**
 * The sections that modules and plugins add to resource pages, by SectionPage. A page
 * that can be extended asks this for its sections' props when it's shown, and for their
 * requests when it's saved; it never knows which sections there are.
 *
 * @public Plugins add their sections from a service provider's boot(), usually through the
 *     Section facade: Section::add(SectionPage::Product, 'acme-notes').
 */
class Sections
{
    /** @var array<string, array<string, SectionDefinition>> By the page's value, then key */
    private array $sections = [];

    /**
     * Adds a section to a page, after the page's own sections and those added before it. Prefix
     * a plugin's key with its name, e.g. "acme-wishlists-notes"; the frontend registers its
     * component under the same page and key.
     */
    public function add(SectionPage $page, string $key): SectionDefinition
    {
        // The key becomes part of field names and error keys, where dots and brackets nest.
        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]*$/', $key) !== 1) {
            throw InvalidSection::invalidKey($key);
        }

        if (isset($this->sections[$page->value][$key])) {
            throw InvalidSection::duplicateKey($page->value, $key);
        }

        return $this->sections[$page->value][$key] = new SectionDefinition($key);
    }

    /**
     * The page's sections with their props, in the order they were added.
     *
     * @return list<SectionData>
     */
    public function data(SectionPage $page, ?Model $resource, SectionContext $context): array
    {
        return array_values(array_map(
            fn (SectionDefinition $section): SectionData => new SectionData($page->value, $section->key, $section->resolveProps($resource, $context)),
            $this->sections[$page->value] ?? [],
        ));
    }

    /**
     * The requests that validate the page's sections while it's saved, each set up with the
     * section's key, the HTTP request, the resource and the context.
     */
    public function requests(SectionPage $page, Request $request, ?Model $resource, SectionContext $context): SectionRequests
    {
        $requests = [];

        foreach ($this->sections[$page->value] ?? [] as $key => $section) {
            if ($class = $section->requestClass()) {
                $requests[$key] = app()->make($class);
                $requests[$key]->setUp($key, $request, $resource, $context);
            }
        }

        return new SectionRequests($requests);
    }
}
