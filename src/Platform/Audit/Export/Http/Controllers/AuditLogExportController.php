<?php

namespace Larasell\Admin\Platform\Audit\Export\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Larasell\Admin\Platform\Audit\Auditor;
use Larasell\Admin\Platform\Audit\Export\Actions\DeleteAuditLogExport;
use Larasell\Admin\Platform\Audit\Export\Actions\RequestAuditLogExport;
use Larasell\Admin\Platform\Audit\Export\Actions\RequestAuditLogExportPayload;
use Larasell\Admin\Platform\Audit\Export\AuditRecords\AuditLogExported;
use Larasell\Admin\Platform\Audit\Export\Http\Requests\DeleteAuditLogExportRequest;
use Larasell\Admin\Platform\Audit\Export\Http\Requests\DownloadAuditLogExportRequest;
use Larasell\Admin\Platform\Audit\Export\Models\AuditLogExport;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @internal Starting, downloading and deleting exports from the audit log page.
 */
class AuditLogExportController
{
    public function store(Request $request, RequestAuditLogExport $requestExport): RedirectResponse
    {
        $admin = $request->user('larasell-admin');
        $gate = Gate::forUser($admin);
        $gate->authorize('larasell.admin.view-audit-log');
        $gate->authorize('larasell.admin.export-audit-log');

        $requestExport->handle(new RequestAuditLogExportPayload((int) $admin?->getKey()));

        return redirect()->route('larasell.admin.settings.audit-log.index');
    }

    public function show(DownloadAuditLogExportRequest $request, AuditLogExport $export, Auditor $auditor): StreamedResponse
    {
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

    public function destroy(DeleteAuditLogExportRequest $request, AuditLogExport $export, DeleteAuditLogExport $delete): RedirectResponse
    {
        $delete->handle($export);

        return redirect()->route('larasell.admin.settings.audit-log.index');
    }
}
