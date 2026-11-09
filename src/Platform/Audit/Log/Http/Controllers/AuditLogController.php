<?php

namespace Larasell\Admin\Platform\Audit\Log\Http\Controllers;

use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\Platform\Audit\Auditor;
use Larasell\Admin\Platform\Audit\Export\Data\AuditLogExportData;
use Larasell\Admin\Platform\Audit\Export\Events\AuditLogExportFinished;
use Larasell\Admin\Platform\Audit\Export\Models\AuditLogExport;
use Larasell\Admin\Platform\Audit\Log\AuditRecords\AuditLogSearched;
use Larasell\Admin\Platform\Audit\Log\AuditRecords\AuditLogViewed;
use Larasell\Admin\Platform\Audit\Log\Data\AuditLogFiltersData;
use Larasell\Admin\Platform\Audit\Log\Data\AuditLogPageData;
use Larasell\Admin\Platform\Audit\Log\Http\Requests\AuditLogFiltersRequest;
use Larasell\Admin\Platform\Audit\Log\Models\AuditLogEntry;
use Larasell\Admin\Platform\Audit\Log\Tables\AuditLog;

/**
 * @internal The audit log page in the settings.
 */
class AuditLogController
{
    public function index(AuditLogFiltersRequest $request, Auditor $auditor): Response
    {
        $admin = $request->user('larasell-admin');
        $gate = Gate::forUser($admin);
        $filters = $request->filters();

        $page = max(1, $request->integer('page', 1));

        // Before showing anything, so no one reads the log without it saying so.
        DB::transaction(fn () => $auditor->record($filters === [] ? new AuditLogViewed($page) : new AuditLogSearched($filters, $page)));

        /** @return list<string> */
        $options = fn (string $column): array => array_values(array_filter(
            AuditLogEntry::query()->whereNotNull($column)->distinct()->orderBy($column)->pluck($column)->all(),
            is_string(...),
        ));

        return Inertia::render('Platform/Audit/Pages/Index', new AuditLogPageData(
            indexUrl: route('larasell.admin.settings.audit-log.index'),
            auditLog: AuditLog::make($filters),
            filters: new AuditLogFiltersData(...$filters),
            eventOptions: $options('event'),
            actorTypeOptions: $options('actor_type'),
            subjectTypeOptions: $options('subject_type'),
            canSearch: $request->filtersEnabled() && $gate->allows('larasell.admin.search-audit-log'),
            exportUrl: $gate->allows('larasell.admin.export-audit-log') ? route('larasell.admin.settings.audit-log.exports.store') : null,
        ))->with('exports', Inertia::live(
            // The admin's own, newest first. Reloaded when one finishes, in every browser they have the page open in.
            fn (): array => AuditLogExport::query()
                ->where('admin_id', $admin?->getAuthIdentifier())
                ->where('expires_at', '>', Date::now()->utc())
                ->latest('created_at')
                ->get()
                ->map(AuditLogExportData::fromExport(...))
                ->all(),
            on: AuditLogExportFinished::class,
            channel: AuditLogExportFinished::channel((string) $admin?->getAuthIdentifier()),
        ));
    }
}
