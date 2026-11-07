<?php

namespace Larasell\Admin\Seo\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A product's or category's URL in one storefront channel, in the content locale.
 */
#[TypeScript]
class SeoUrlData extends Data
{
    public function __construct(
        public int $channelId,
        /** The channel's name. */
        public string $channel,
        /** Where the channel's storefront starts in the content locale, e.g. "https://shop.de/". */
        public string $baseUrl,
        /** The path the admin picked, null while the URL follows the name. */
        public ?string $customPath,
        /** The path made from the name, null before the product or category has one in the locale. */
        public ?string $automaticPath,
        /** Its earlier URLs in the channel, which redirect to the current one. */
        public OldUrlsData $history,
    ) {}
}
