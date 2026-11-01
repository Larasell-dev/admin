<?php

namespace Larasell\Admin\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * A stored audit record, read for the audit log page. Records are written by the Auditor and
 * never changed or deleted through the app, so this model refuses to.
 *
 * @internal Record with Auditor::record(); this model and its columns may change.
 *
 * @property string $id
 * @property string $event
 * @property int $event_version
 * @property string $category
 * @property string $actor_type
 * @property ?string $actor_id
 * @property ?string $unknown_actor_reason
 * @property ?string $subject_type
 * @property ?string $subject_id
 * @property array<string, mixed> $properties
 * @property string $source
 * @property ?string $correlation_id
 * @property CarbonInterface $occurred_at
 * @property CarbonInterface $created_at
 */
class AuditLogEntry extends Model
{
    use HasUlids;

    protected $table = 'larasell_audit_log';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'event_version' => 'integer',
            'properties' => 'array',
            'occurred_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        $refuse = fn () => throw new LogicException('Audit records are append-only: record new ones with Auditor::record().');

        static::saving($refuse);
        static::deleting($refuse);
    }
}
