<?php

namespace Larasell\Admin\Platform\Audit\Export\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Larasell\Admin\Platform\Audit\Auditor;
use Larasell\Admin\Platform\Audit\Export\AuditLogExporter;
use Larasell\Admin\Platform\Audit\Export\AuditRecords\AuditLogExportDeleted;
use Larasell\Admin\Platform\Audit\Export\AuditRecords\AuditLogExported;
use Larasell\Admin\Platform\Audit\Export\AuditRecords\AuditLogExportRequested;
use Larasell\Admin\Platform\Audit\Export\Events\AuditLogExportFinished;
use Larasell\Admin\Platform\Audit\Export\Jobs\DeleteExpiredAuditLogExport;
use Larasell\Admin\Platform\Audit\Export\Jobs\ExportAuditLog;
use Larasell\Admin\Platform\Audit\Export\Models\AuditLogExport;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @internal Starting and downloading exports from the audit log page.
 */
class AuditLogExportController
{
    /**
     * How long an export can be downloaded before it is deleted.
     */
    public const int HOURS_KEPT = 24;

    public function store(Request $request, Auditor $auditor, AuditLogExporter $exporter): RedirectResponse
    {
        $gate = Gate::forUser($request->user('larasell-admin'));
        $gate->authorize('larasell.admin.view-audit-log');
        $gate->authorize('larasell.admin.export-audit-log');

        $export = DB::transaction(function () use ($auditor, $exporter): AuditLogExport {
            $export = AuditLogExport::query()->create([
                'admin_id' => Auth::guard('larasell-admin')->id(),
                'format' => $exporter->format->extension(),
                'status' => 'pending',
                'created_at' => Date::now()->utc(),
                'expires_at' => Date::now()->utc()->addHours(self::HOURS_KEPT),
            ]);

            $auditor->record(new AuditLogExportRequested($export->id, $export->format));

            return $export;
        });

        // After the commit, so the job finds the export and the chain includes its request.
        ExportAuditLog::dispatch($export->id);
        // Deletes it once expired, even where no one scheduled larasell:prune-audit-log.
        DeleteExpiredAuditLogExport::dispatch($export->id)->delay($export->expires_at);

        return redirect()->route('larasell.admin.settings.audit-log.index');
    }

    public function show(Request $request, AuditLogExport $export, Auditor $auditor, AuditLogExporter $exporter): StreamedResponse
    {
        $admin = $request->user('larasell-admin');

        abort_unless($export->admin_id === Auth::guard('larasell-admin')->id() && $export->status === 'ready' && $export->expires_at->isFuture(), 403);
        Gate::forUser($admin)->authorize('larasell.admin.export-audit-log');

        // Opened first, so a file that is gone records no download; recorded before any byte leaves.
        $stream = Storage::disk(config('larasell.admin.audit.export.disk'))->readStream((string) $export->path);
        abort_if($stream === null, 404);

        try {
            DB::transaction(fn () => $auditor->record(new AuditLogExported($export->id, $export->format, (int) $export->records)));
        } catch (\Throwable $exception) {
            fclose($stream);

            throw $exception;
        }

        return response()->streamDownload(
            function () use ($stream): void {
                fpassthru($stream);
                fclose($stream);
            },
            'audit-log-'.$export->created_at->utc()->format('Y-m-d-His').'.zip',
            ['Content-Type' => 'application/zip'],
        );
    }

    /**
     * Deletes the admin's own export before it expires, once they typed the confirmation.
     */
    public function destroy(Request $request, AuditLogExport $export, Auditor $auditor): RedirectResponse
    {
        abort_unless($export->admin_id === Auth::guard('larasell-admin')->id(), 403);

        $expected = __('Delete export');

        if ($request->string('confirmation')->trim()->value() !== $expected) {
            throw ValidationException::withMessages(['confirmation' => __('Type ":text" to confirm.', ['text' => $expected])]);
        }

        DB::transaction(function () use ($export, $auditor): void {
            $auditor->record(new AuditLogExportDeleted($export->id, $export->format));
            DeleteExpiredAuditLogExport::delete($export);
        });
        AuditLogExportFinished::dispatch($export->admin_id);

        return redirect()->route('larasell.admin.settings.audit-log.index');
    }
}
