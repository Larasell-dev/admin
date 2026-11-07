<?php

namespace Larasell\Admin\Seo\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * An earlier URL of a product or category in one channel, which redirects to its current one.
 */
#[TypeScript]
class OldUrlData extends Data
{
    public function __construct(
        public int $id,
        /** Below the channel's base URL, e.g. "p/boots". */
        public string $path,
        /** Where to DELETE it, so it no longer redirects. */
        public string $removeUrl,
        /** Where to POST to make it the current URL again. */
        public string $restoreUrl,
    ) {}
}
