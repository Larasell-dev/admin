<?php

namespace Larasell\Admin\Platform\Sections\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A section of a page, which the panel renders with the component registered for its page and
 * key.
 */
#[TypeScript]
class SectionData extends Data
{
    public function __construct(
        /** The page it extends, e.g. "product". */
        public string $page,
        public string $key,
        public mixed $props,
    ) {}
}
