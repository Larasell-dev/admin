<?php

namespace Larasell\Admin\Seo\Actions;

/**
 * A product's or category's URLs as its page saved them.
 */
final readonly class SeoUrlsPayload
{
    public function __construct(
        /** @var array<int, string|null> By channel ID: the custom path, or null to follow the name */
        public array $paths,
    ) {}
}
