<?php

namespace Workbench\App\TypeScript;

use ReflectionClass;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\TypeScriptTransformer\Collectors\EnumCollector;

/**
 * Emits only the backed enums marked #[TypeScript], as a union of their values; the parent
 * would emit every enum in src.
 */
class TypeScriptEnumCollector extends EnumCollector
{
    protected function shouldCollect(ReflectionClass $class): bool
    {
        return parent::shouldCollect($class) && $class->getAttributes(TypeScript::class) !== [];
    }
}
