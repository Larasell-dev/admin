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
 * @public Plugins add sections with Section::add(), the facade of Sections.
 */
final class SectionDefinition
{
    /** @var (Closure(?Model, SectionContext): mixed)|class-string<Props>|null */
    private Closure|string|null $props = null;

    /** @var class-string<SectionRequest>|null */
    private ?string $request = null;

    public function __construct(public readonly string $key) {}

    /**
     * What the section's component gets as `props`, e.g. what it saved before: a Props class,
     * which the container makes, or a closure that takes the same arguments as Props::resolve().
     *
     * @param  (Closure(?Model, SectionContext): mixed)|class-string<Props>  $props
     */
    public function props(Closure|string $props): self
    {
        if (is_string($props) && ! is_subclass_of($props, Props::class)) {
            throw new InvalidArgumentException("The props of the [{$this->key}] section must implement [".Props::class."], [{$props}] doesn't.");
        }

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
        return match (true) {
            $this->props === null => null,
            is_string($this->props) => app()->make($this->props)->resolve($resource, $context),
            default => ($this->props)($resource, $context),
        };
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
