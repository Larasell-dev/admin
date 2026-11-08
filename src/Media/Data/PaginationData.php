<?php

namespace Larasell\Admin\Media\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** Where the library's grid is, in the shape the tables' Pagination takes. */
#[TypeScript]
class PaginationData extends Data
{
    public function __construct(
        /** 1-based. */
        public int $page,
        public int $pageSize,
        public int $total,
    ) {}
}
