<?php

namespace Larasell\Admin\Platform\Audit\Export\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * An admin's export of the audit log is ready to download, or failed, so their open audit log
 * pages reload their exports. Only to that admin, since exports are their own.
 */
final class AuditLogExportFinished implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public readonly int|string $adminId) {}

    public static function channel(int|string $adminId): PrivateChannel
    {
        return new PrivateChannel("larasell-admin.audit-log-exports.{$adminId}");
    }

    public function broadcastOn(): PrivateChannel
    {
        return self::channel($this->adminId);
    }

    public function broadcastAs(): string
    {
        return 'audit-log-export.finished';
    }

    /** @return array<string, mixed> Nothing: the page reloads its exports itself. */
    public function broadcastWith(): array
    {
        return [];
    }
}
