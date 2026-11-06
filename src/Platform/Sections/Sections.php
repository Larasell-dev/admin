<?php

namespace Larasell\Admin\Platform\Sections;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Larasell\Admin\Platform\Sections\Data\SectionData;

/**
 * The sections that modules and plugins add to resource pages, by page, e.g. "product". A page
 * that can be extended asks this for its sections' props when it's shown, and for their
 * requests when it's saved; it never knows which sections there are.
 *
 * @public Plugins add their sections from a service provider's boot().
 */
class Sections
{
    /** @var array<string, array<string, Section>> */
    private array $sections = [];

    /**
     * Adds a section to a page, after the page's own sections and those added before it. Prefix
     * a plugin's key with its name, e.g. "acme-wishlists-notes"; the frontend registers its
     * component under the same page and key.
     */
    public function add(string $page, string $key): Section
    {
        // The key becomes part of field names and error keys, where dots and brackets nest.
        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]*$/', $key) !== 1) {
            throw new InvalidArgumentException("The section key [{$key}] may only contain letters, numbers, dashes and underscores.");
        }

        if (isset($this->sections[$page][$key])) {
            throw new InvalidArgumentException("The [{$page}] page already has a section [{$key}].");
        }

        return $this->sections[$page][$key] = new Section($key);
    }

    /**
     * The page's sections with their props, in the order they were added.
     *
     * @return list<SectionData>
     */
    public function data(string $page, ?Model $resource, SectionContext $context): array
    {
        return array_values(array_map(
            fn (Section $section): SectionData => new SectionData($page, $section->key, $section->resolveProps($resource, $context)),
            $this->sections[$page] ?? [],
        ));
    }

    /**
     * The requests that validate the page's sections while it's saved, each set up with the
     * section's key, the HTTP request, the resource and the context.
     */
    public function requests(string $page, Request $request, ?Model $resource, SectionContext $context): SectionRequests
    {
        $requests = [];

        foreach ($this->sections[$page] ?? [] as $key => $section) {
            if ($class = $section->requestClass()) {
                $requests[$key] = app()->make($class);
                $requests[$key]->setUp($key, $request, $resource, $context);
            }
        }

        return new SectionRequests($requests);
    }
}
