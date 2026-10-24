<?php

namespace Larasell\Admin\Data\ViewModel;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class NavigationItemData extends Data
{
    public function __construct(
        public string $id,
        public string $label,
        public string $url,
        /** Icon case name, e.g. "Home"; @larasell-dev/ui/icons exports it with a Fill or Line suffix. */
        public string $icon,
        /**
         * Nested items; always empty for a child, since nesting is one level deep.
         *
         * @var array<int, NavigationItemData>
         */
        public array $children,
    ) {}
}
