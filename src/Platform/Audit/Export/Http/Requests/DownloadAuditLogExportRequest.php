<?php

namespace Larasell\Admin\Platform\Audit\Export\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Larasell\Admin\Platform\Audit\Export\Enums\AuditLogExportStatus;
use Larasell\Admin\Platform\Audit\Export\Models\AuditLogExport;

/**
 * Only the admin who asked for an export may download it, once ready and until it expires.
 *
 * @internal
 */
class DownloadAuditLogExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var AuditLogExport $export */
        $export = $this->route('export');
        $admin = $this->user('larasell-admin');

        return $admin !== null
            && $export->admin_id === $admin->getKey()
            && $export->status === AuditLogExportStatus::Ready
            && $export->expires_at->isFuture()
            && Gate::forUser($admin)->allows('larasell.admin.export-audit-log');
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [];
    }
}
