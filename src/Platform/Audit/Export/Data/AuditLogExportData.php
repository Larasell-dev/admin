<?php

namespace Larasell\Admin\Platform\Audit\Export\Data;

use Illuminate\Support\Facades\URL;
use Larasell\Admin\Platform\Audit\Export\Enums\AuditLogExportStatus;
use Larasell\Admin\Platform\Audit\Export\Models\AuditLogExport;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * @internal An export on the audit log page.
 */
#[TypeScript]
class AuditLogExportData extends Data
{
    public function __construct(
        public string $id,
        public AuditLogExportStatus $status,
        public ?int $records,
        public string $createdAt,
        public string $expiresAt,
        public ?string $downloadUrl,
        public string $deleteUrl,
    ) {}

    public static function fromExport(AuditLogExport $export): self
    {
        return new self(
            id: $export->id,
            status: $export->status,
            records: $export->records,
            createdAt: $export->created_at->toIso8601String(),
            expiresAt: $export->expires_at->toIso8601String(),
            downloadUrl: $export->status === AuditLogExportStatus::Ready
                ? URL::temporarySignedRoute('larasell.admin.settings.audit-log.exports.show', $export->expires_at, ['export' => $export->id])
                : null,
            deleteUrl: route('larasell.admin.settings.audit-log.exports.destroy', $export->id),
        );
    }
}
