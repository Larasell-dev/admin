<?php

namespace Larasell\Admin\Platform\Audit\Export\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Larasell\Admin\Platform\Audit\Export\Enums\AuditLogExportStatus;

/**
 * An export of the audit log an admin asked for, and the file it was written to once ready.
 *
 * @internal
 *
 * @property int $id
 * @property int $admin_id
 * @property string $format The extension of the file in the zip archive, e.g. json.
 * @property AuditLogExportStatus $status
 * @property ?string $path
 * @property ?int $records
 * @property CarbonInterface $created_at
 * @property CarbonInterface $expires_at
 * @property ?CarbonInterface $started_at
 */
class AuditLogExport extends Model
{
    protected $table = 'larasell_audit_log_exports';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => AuditLogExportStatus::class,
            'records' => 'integer',
            'created_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
            'started_at' => 'immutable_datetime',
        ];
    }
}
