<?php

namespace Larasell\Admin\Http\Controllers\AuditLog;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\Audit\Auditor;
use Larasell\Admin\AuditRecords\AuditLog\AuditLogSearched;
use Larasell\Admin\AuditRecords\AuditLog\AuditLogViewed;
use Larasell\Admin\Data\View\AuditLog\AuditLogFiltersData;
use Larasell\Admin\Data\View\AuditLog\AuditLogPageData;
use Larasell\Admin\Models\AuditLogEntry;
use Larasell\Admin\Tables\AuditLog\AuditLog;

/**
 * @internal The audit log page in the settings.
 */
class AuditLogController
{
    public function index(Request $request, Auditor $auditor): Response
    {
        $gate = Gate::forUser($request->user('larasell-admin'));
        $gate->authorize('viewAuditLog');

        /** @var array{event?: string, actor_type?: string, actor_id?: string, subject_type?: string, subject_id?: string, from?: string, until?: string} $filters */
        $filters = array_filter($request->validate([
            'event' => ['nullable', 'string', 'max:255'],
            'actor_type' => ['nullable', 'string', 'max:255', 'required_with:actor_id'],
            'actor_id' => ['nullable', 'string', 'max:255'],
            'subject_type' => ['nullable', 'string', 'max:255', 'required_with:subject_id'],
            'subject_id' => ['nullable', 'string', 'max:255'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'until' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]), fn (mixed $value): bool => $value !== null);

        if ($filters !== []) {
            $gate->authorize('searchAuditLog');
        }

        $page = max(1, $request->integer('page', 1));

        // Before showing anything, so no one reads the log without it saying so.
        DB::transaction(fn () => $auditor->record($filters === [] ? new AuditLogViewed($page) : new AuditLogSearched($filters, $page)));

        /** @return list<string> */
        $options = fn (string $column): array => array_values(array_filter(
            AuditLogEntry::query()->whereNotNull($column)->distinct()->orderBy($column)->pluck($column)->all(),
            is_string(...),
        ));

        return Inertia::render('AuditLog/Index', new AuditLogPageData(
            indexUrl: route('larasell.admin.settings.audit-log.index'),
            auditLog: AuditLog::make($filters),
            filters: new AuditLogFiltersData(...$filters),
            eventOptions: $options('event'),
            actorTypeOptions: $options('actor_type'),
            subjectTypeOptions: $options('subject_type'),
            canSearch: $gate->allows('searchAuditLog'),
        ));
    }
}
