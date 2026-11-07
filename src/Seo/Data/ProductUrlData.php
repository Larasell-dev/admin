<?php

namespace Larasell\Admin\Seo\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * The product's URL in one storefront channel, in the content locale.
 */
#[TypeScript]
class ProductUrlData extends Data
{
    public function __construct(
        public int $channelId,
        /** The channel's name. */
        public string $channel,
        /** Where the channel's storefront starts in the content locale, e.g. "https://shop.de/". */
        public string $baseUrl,
        /** The path the admin picked, null while the URL follows the product's name. */
        public ?string $customPath,
        /** The path made from the product's name, null before the product has one in the locale. */
        public ?string $automaticPath,
    ) {}
}
