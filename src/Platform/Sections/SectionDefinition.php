<?php

namespace Larasell\Admin\Platform\Sections;

use BackedEnum;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Larasell\Admin\Platform\Sections\Exceptions\InvalidSection;
use Larasell\Admin\Platform\Support\Assert;

/**
 * A section that a module or plugin adds to a form, e.g. the product form. Its component renders
 * inside the form, so its fields are saved with it; they post under "sections[{key}]" and its
 * request sees only them, so sections never clash.
 *
 * Without before() or after() it goes after the form's own sections. Sections in the same place
 * are sorted by position(), then alphabetically by key.
 *
 * @public Plugins add sections with Section::add(), the facade of Sections.
 */
final class SectionDefinition
{
    /** @var (Closure(?Model, SectionContext): mixed)|class-string<Props>|null */
    private Closure|string|null $props = null;

    /** @var class-string<SectionRequest>|null */
    private ?string $request = null;

    private ?BackedEnum $before = null;

    private ?BackedEnum $after = null;

    private int $position = 0;

    private ?string $requires = null;

    /** Whether it was set up wrongly, so in production its form leaves it out. */
    private bool $broken = false;

    public function __construct(public readonly string $key) {}

    /**
     * What the section's component gets as `props`, e.g. what it saved before: a Props class,
     * which the container makes, or a closure that takes the same arguments as Props::resolve().
     *
     * @param  (Closure(?Model, SectionContext): mixed)|class-string<Props>  $props
     */
    public function props(Closure|string $props): self
    {
        if (! Assert::that(! is_string($props) || is_subclass_of($props, Props::class), fn () => InvalidSection::invalidProps($this->key, is_string($props) ? $props : Closure::class))) {
            $this->broken = true;

            return $this;
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
        if (! Assert::that(is_subclass_of($request, SectionRequest::class), fn () => InvalidSection::invalidRequest($this->key, $request))) {
            $this->broken = true;

            return $this;
        }

        $this->request = $request;

        return $this;
    }

    /**
     * Places the section right before one of the form's own sections, e.g. ProductSection::General.
     */
    public function before(BackedEnum $section): self
    {
        [$this->before, $this->after] = [$section, null];

        return $this;
    }

    /**
     * Places the section right after one of the form's own sections, e.g. ProductSection::Stock.
     */
    public function after(BackedEnum $section): self
    {
        [$this->before, $this->after] = [null, $section];

        return $this;
    }

    /**
     * Orders the section among others in the same place: lower first, 0 by default. Sections with
     * the same position are sorted alphabetically by key, so the order doesn't depend on which
     * plugin booted first.
     */
    public function position(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Shows the section only when the resource has the capability, e.g. a product whose type has
     * "shipping". Its request is left out too, so its fields are neither validated nor saved.
     */
    public function requires(string $capability): self
    {
        $this->requires = $capability;

        return $this;
    }

    /** @internal */
    public function appliesTo(SectionContext $context): bool
    {
        return $this->requires === null || $context->capabilities === null || in_array($this->requires, $context->capabilities, true);
    }

    /**
     * @internal
     *
     * @return array{before: ?BackedEnum, after: ?BackedEnum, position: int}
     */
    public function placement(): array
    {
        return ['before' => $this->before, 'after' => $this->after, 'position' => $this->position];
    }

    /** @internal */
    public function isBroken(): bool
    {
        return $this->broken;
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
