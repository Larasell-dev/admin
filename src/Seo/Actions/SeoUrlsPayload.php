<?php

namespace Larasell\Admin\Seo\Actions;

use Larasell\Larasell\Enums\Locale;

/**
 * A product's or category's URLs as its page saved them.
 */
final readonly class SeoUrlsPayload
{
    public function __construct(
        /** The content locale the page edited, which the paths are in. */
        public Locale $locale,
        /** @var array<int, string|null> By channel ID: the custom path, or null to follow the name */
        public array $paths,
    ) {}
}
