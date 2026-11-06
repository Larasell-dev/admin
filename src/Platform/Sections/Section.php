<?php

namespace Larasell\Admin\Platform\Sections;

use Closure;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * A section that a module or plugin adds to a resource page, e.g. a product's. Its component
 * renders inside the page's form, so its fields are saved with the page; they post under
 * "sections[{key}]" and its request sees only them, so sections never clash.
 *
 * @public Plugins add sections with Sections::add().
 */
final class Section
{
    /** @var (Closure(?Model, SectionContext): mixed)|null */
    private ?Closure $props = null;

    /** @var class-string<SectionRequest>|null */
    private ?string $request = null;

    public function __construct(public readonly string $key) {}

    /**
     * What the section's component gets as `props`, e.g. what it saved before. The resource is
     * null on the create page.
     *
     * @param  Closure(?Model, SectionContext): mixed  $props
     */
    public function props(Closure $props): self
    {
        $this->props = $props;

        return $this;
    }

    /**
     * The class that validates the section's fields when the page is saved, and turns them into
     * its payload. Without one, the section has no fields to save.
     *
     * @param  class-string<SectionRequest>  $request
     */
    public function request(string $request): self
    {
        if (! is_subclass_of($request, SectionRequest::class)) {
            throw new InvalidArgumentException("The request of the [{$this->key}] section must extend [".SectionRequest::class."], [{$request}] doesn't.");
        }

        $this->request = $request;

        return $this;
    }

    /** @internal */
    public function resolveProps(?Model $resource, SectionContext $context): mixed
    {
        return $this->props === null ? null : ($this->props)($resource, $context);
    }

    /**
     * @internal
     *
     * @return class-string<SectionRequest>|null
     */
    public function requestClass(): ?string
    {
        return $this->request;
    }
}
