<?php

namespace Larasell\Admin\SalesChannel\Data;

use Larasell\Larasell\Storefront\Models\EntryPoint;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * One page of a storefront's domains by language, primary first, picked by the query parameters
 * domains[page] and domains[size].
 */
#[TypeScript]
class EntryPointsData extends Data
{
    /** Domains per page, the first by default. */
    public const PAGE_SIZES = [10, 25, 50, 100];

    public function __construct(
        /** @var array<int, EntryPointData> */
        public array $entryPoints,
        /** 1-based. */
        public int $page,
        public int $pageSize,
        /** Across all pages. */
        public int $total,
    ) {}

    /**
     * The page that domains[page] and [size] ask for; the last one when they ask for one past the
     * end, e.g. after the last domain on it was deleted.
     */
    public static function forChannel(int $channelId): self
    {
        $query = EntryPoint::query()->where('sales_channel_id', $channelId);
        $total = (clone $query)->count();
        $requested = request()->query('domains');
        $requested = is_array($requested) ? $requested : [];
        $size = in_array((int) ($requested['size'] ?? 0), self::PAGE_SIZES, true) ? (int) $requested['size'] : self::PAGE_SIZES[0];
        $page = max(1, min((int) ($requested['page'] ?? 1), (int) ceil($total / $size)));

        $entryPoints = $query->orderBy('locale')->orderByDesc('is_primary')->orderBy('id')
            ->forPage($page, $size)
            ->get()->map(EntryPointData::fromModel(...))->all();

        return new self($entryPoints, $page, $size, $total);
    }
}
