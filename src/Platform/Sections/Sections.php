<?php

namespace Larasell\Admin\Platform\Sections;

use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Larasell\Admin\Platform\Sections\Data\SectionData;
use Larasell\Admin\Platform\Sections\Exceptions\InvalidSection;
use Larasell\Admin\Platform\Support\Assert;

/**
 * The sections that modules and plugins add to forms, by SectionForm. A form that can be extended
 * registers its own sections as anchors, asks this for its sections' props when its page is
 * shown, and for their requests when it's saved; it never knows which sections there are.
 *
 * @public Plugins add their sections from a service provider's boot(), usually through the
 *     Section facade: Section::add(SectionForm::Product, 'acme-notes').
 */
class Sections
{
    /** @var array<string, array<string, SectionDefinition>> By the form's value, then key */
    private array $sections = [];

    /** @var array<string, class-string<BackedEnum>> The enum of each form's own sections */
    private array $anchors = [];

    /**
     * Adds a section to a form. Prefix a plugin's key with its name, e.g. "acme-wishlists-notes";
     * the frontend registers its component under the same form and key. Place it with before(),
     * after() and position(); without them it goes after the form's own sections.
     */
    public function add(SectionForm $form, string $key): SectionDefinition
    {
        $section = new SectionDefinition($key);

        // The key becomes part of field names and error keys, where dots and brackets nest. In
        // production a broken section is left off its form, and the form works without it.
        if (! Assert::that(preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]*$/', $key) === 1, fn () => InvalidSection::invalidKey($key))) {
            return $section;
        }

        // The first section with the key keeps it.
        if (! Assert::that(! isset($this->sections[$form->value][$key]), fn () => InvalidSection::duplicateKey($form->value, $key))) {
            return $section;
        }

        return $this->sections[$form->value][$key] = $section;
    }

    /**
     * Says which enum lists the form's own sections, which sections can be placed before or
     * after. The module that owns the form registers it, e.g. ProductSection for the product form.
     *
     * @param  class-string<BackedEnum>  $sections
     */
    public function anchors(SectionForm $form, string $sections): void
    {
        $this->anchors[$form->value] = $sections;
    }

    /**
     * The form's sections with their props and places, sorted by position, then by key.
     *
     * @return list<SectionData>
     */
    public function data(SectionForm $form, ?Model $resource, SectionContext $context): array
    {
        $sections = array_filter($this->sections[$form->value] ?? [], fn (SectionDefinition $section): bool => ! $section->isBroken() && $section->appliesTo($context));

        uasort($sections, fn (SectionDefinition $a, SectionDefinition $b): int => [$a->placement()['position'], $a->key] <=> [$b->placement()['position'], $b->key]);

        return array_values(array_map(function (SectionDefinition $section) use ($form, $resource, $context): SectionData {
            ['before' => $before, 'after' => $after] = $section->placement();

            // Checked here, once every provider has booted, so it doesn't matter which registered
            // first. The form's outlets rely on it too; in production the section goes after the
            // form's own sections instead.
            foreach (array_filter([$before, $after]) as $anchor) {
                $known = isset($this->anchors[$form->value]) && $anchor instanceof $this->anchors[$form->value];

                if (! Assert::that($known, fn () => InvalidSection::unknownAnchor($form->value, $section->key, $anchor, $this->anchors[$form->value] ?? null))) {
                    [$before, $after] = [null, null];
                }
            }

            return new SectionData(
                $form->value,
                $section->key,
                $section->resolveProps($resource, $context),
                $before === null ? null : (string) $before->value,
                $after === null ? null : (string) $after->value,
            );
        }, $sections));
    }

    /**
     * The requests that validate the form's sections while it's saved, each set up with the
     * section's key, the HTTP request, the resource and the context.
     */
    public function requests(SectionForm $form, Request $request, ?Model $resource, SectionContext $context): SectionRequests
    {
        $requests = [];

        foreach ($this->sections[$form->value] ?? [] as $key => $section) {
            if (! $section->isBroken() && $section->appliesTo($context) && $class = $section->requestClass()) {
                $requests[$key] = app()->make($class);
                $requests[$key]->setUp($key, $request, $resource, $context);
            }
        }

        return new SectionRequests($requests);
    }
}
