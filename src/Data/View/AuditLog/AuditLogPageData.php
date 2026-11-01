<?php

namespace Larasell\Admin\Data\View\AuditLog;

use Larasell\Admin\Tables\AuditLog\AuditLog;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * @internal
 */
#[TypeScript]
class AuditLogPageData extends Data
{
    /**
     * @param  list<string>  $eventOptions
     * @param  list<string>  $actorTypeOptions
     * @param  list<string>  $subjectTypeOptions
     */
    public function __construct(
        public string $indexUrl,
        #[LiteralTypeScriptType('import("./index").TableResource')]
        public AuditLog $auditLog,
        public AuditLogFiltersData $filters,
        public array $eventOptions,
        public array $actorTypeOptions,
        public array $subjectTypeOptions,
        public bool $canSearch,
    ) {}
}
