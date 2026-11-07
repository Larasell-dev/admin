<?php

namespace Larasell\Admin\SalesChannel\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A sales channel was created, renamed or deleted, so every signed-in admin's sidebar reloads its
 * list. Sent once the change commits, so a change that was refused and rolled back sends nothing,
 * and to everyone but the browser that made it, which gets the list with its next page.
 */
final class SalesChannelsChanged implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets;

    public const CHANNEL = 'larasell-admin.sales-channels';

    public function __construct()
    {
        $this->dontBroadcastToCurrentUser();
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel(self::CHANNEL);
    }

    public function broadcastAs(): string
    {
        return 'sales-channels.changed';
    }

    /** @return array<string, mixed> Nothing: the sidebar reloads the list itself. */
    public function broadcastWith(): array
    {
        return [];
    }
}
