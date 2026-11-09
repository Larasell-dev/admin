<?php

namespace Larasell\Admin\Media\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Something in the media library changed: media were uploaded, edited, moved or deleted, or a
 * folder was created, renamed, moved or deleted. Every other signed-in admin's library reloads its
 * folders and grid. Sent once the change commits, so a refused change sends nothing. The core
 * knows nothing of this: the panel listens to the core's media events and sends it.
 */
final class MediaLibraryChanged implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets;

    public const CHANNEL = 'larasell-admin.media';

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
        return 'media.changed';
    }

    /** @return array<string, mixed> Nothing: the library reloads its props itself. */
    public function broadcastWith(): array
    {
        return [];
    }
}
