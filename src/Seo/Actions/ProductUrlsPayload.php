<?php

namespace Larasell\Admin\Seo\Actions;

/**
 * The product's URLs as the page saved them.
 */
final readonly class ProductUrlsPayload
{
    public function __construct(
        /** @var array<int, string|null> By channel ID: the custom path, or null to follow the product's name */
        public array $paths,
    ) {}
}
