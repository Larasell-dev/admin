<?php

namespace Larasell\Admin\Seo\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * One page of a product's or category's earlier URLs in one channel, newest first. Each channel's
 * list pages on its own, by the query parameters old-urls[{channel ID}][page] and [size].
 */
#[TypeScript]
class OldUrlsData extends Data
{
    public function __construct(
        /** @var array<int, OldUrlData> */
        public array $urls,
        /** 1-based. */
        public int $page,
        public int $pageSize,
        /** Across all pages. */
        public int $total,
    ) {}
}
