<?php

namespace Larasell\Admin\SalesChannel\Data;

use Inertia\Inertia;
use Inertia\OnceProp;
use Larasell\Larasell\SalesChannels\Models\SalesChannel;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * The sales channels section of the sidebar: every channel by name, and where to add one.
 */
#[TypeScript]
class SalesChannelNavigationData extends Data
{
    /** Flashed by a write, so the next page sends the section again. */
    private const CHANGED = 'larasell.admin.sales-channels.changed';

    public function __construct(
        /** @var array<int, SalesChannelLinkData> */
        public array $channels,
        public string $createUrl,
    ) {}

    /**
     * Sent once, then kept by the browser across pages, until a sales channel is created,
     * renamed or deleted in this session.
     */
    public static function prop(): OnceProp
    {
        return Inertia::once(fn (): self => new self(
            channels: SalesChannel::query()->orderBy('name')->orderBy('id')->get(['id', 'name'])
                ->map(fn (SalesChannel $channel): SalesChannelLinkData => new SalesChannelLinkData(
                    id: $channel->id,
                    name: $channel->name,
                    url: route('larasell.admin.sales-channels.edit', $channel),
                ))->all(),
            createUrl: route('larasell.admin.sales-channels.create'),
        ))->fresh(session()->has(self::CHANGED));
    }

    public static function refreshOnNextPage(): void
    {
        session()->flash(self::CHANGED, true);
    }
}
