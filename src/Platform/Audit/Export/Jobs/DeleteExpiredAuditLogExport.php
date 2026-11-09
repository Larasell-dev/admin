<?php

namespace Larasell\Admin\Platform\Audit\Export\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Larasell\Admin\Platform\Audit\Export\Exceptions\InvalidAuditLogExport;
use Larasell\Admin\Platform\Audit\Export\Models\AuditLogExport;

/**
 * Deletes an export once it expires. Dispatched with a delay when the export is asked for, so
 * exports go even where larasell:prune-audit-log isn't scheduled; prune catches any it missed.
 *
 * @internal
 */
class DeleteExpiredAuditLogExport implements ShouldQueue
{
    use Queueable;

    /** Where ExportAuditLog writes exports on the export disk. */
    public const string FOLDER = 'larasell/audit-log-exports';

    public function __construct(public readonly int $exportId) {}

    public function handle(): void
    {
        $export = AuditLogExport::query()->find($this->exportId);

        // Queues that ignore the delay, like sync, run it early; it waits for prune then.
        if ($export !== null && ! $export->expires_at->isFuture()) {
            self::delete($export);
        }
    }

    /**
     * The row first, then the file, so a failure leaves at worst a file no one can reach.
     *
     * @internal Also called by larasell:prune-audit-log.
     */
    public static function delete(AuditLogExport $export): void
    {
        // The only code that deletes files: never one the export didn't write.
        if ($export->path !== null && ! self::inFolder($export->path)) {
            throw InvalidAuditLogExport::outsideFolder($export->id, $export->path);
        }

        $export->delete();

        if ($export->path !== null) {
            Storage::disk(config('larasell.admin.audit.export.disk'))->delete($export->path);
        }
    }

    /**
     * Whether the path names a file directly in the exports folder, as ExportAuditLog writes them.
     */
    private static function inFolder(string $path): bool
    {
        return preg_match('#^'.preg_quote(self::FOLDER, '#').'/[A-Za-z0-9_-]+\.zip$#', $path) === 1;
    }
}
