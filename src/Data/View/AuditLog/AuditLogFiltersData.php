<?php

namespace Larasell\Admin\Data\View\AuditLog;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * The audit log's filters as the page has them; days in the app's time zone, as Y-m-d.
 *
 * @internal
 */
#[TypeScript]
class AuditLogFiltersData extends Data
{
    public function __construct(
        public ?string $event = null,
        public ?string $actor_type = null,
        public ?string $actor_id = null,
        public ?string $subject_type = null,
        public ?string $subject_id = null,
        public ?string $from = null,
        public ?string $until = null,
    ) {}
}
