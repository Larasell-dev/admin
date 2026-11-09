<?php

namespace Larasell\Admin\Platform\Settings\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A settings page as its card on the settings index, and its command menu entry, show it.
 */
#[TypeScript]
class SettingsPageData extends Data
{
    public function __construct(
        public string $id,
        public string $label,
        public ?string $description,
        /** Icon case name, e.g. "Team"; @larasell-dev/ui/icons exports it with a Fill or Line suffix. */
        public string $icon,
        public string $url,
    ) {}
}
