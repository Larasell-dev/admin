<?php

namespace Larasell\Admin\Platform\Sections\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A section of a form, which the panel renders with the component registered for its form and
 * key, before or after one of the form's own sections, or after all of them.
 */
#[TypeScript]
class SectionData extends Data
{
    public function __construct(
        /** The form it extends, a SectionForm value, e.g. "product". */
        public string $form,
        public string $key,
        public mixed $props,
        /** The form's own section it goes right before, e.g. "general". */
        public ?string $before,
        /** The form's own section it goes right after; with neither, it goes after all of them. */
        public ?string $after,
    ) {}
}
