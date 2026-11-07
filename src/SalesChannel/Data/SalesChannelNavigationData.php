<?php

namespace Larasell\Admin\SalesChannel\Data;

use Inertia\Inertia;
use Inertia\OnceProp;
use Larasell\Admin\SalesChannel\Events\SalesChannelsChanged;
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
     * Sent once, then kept by the browser across pages. When a sales channel is created, renamed
     * or deleted, this session gets it again with its next page, and every other signed-in
     * admin's browser reloads it as soon as SalesChannelsChanged is broadcast.
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
        ))->fresh(session()->has(self::CHANGED))->live(on: SalesChannelsChanged::class);
    }

    public static function refreshOnNextPage(): void
    {
        session()->flash(self::CHANGED, true);
    }
}
