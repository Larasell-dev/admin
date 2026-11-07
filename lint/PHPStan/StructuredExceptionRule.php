<?php

namespace Larasell\Admin\Lint\PHPStan;

use Larasell\Chronicle\Exceptions\StructuredException;
use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use Throwable;

/**
 * The panel's exceptions tell developers why something failed and how to fix it, so they are
 * structured: a class in an Exceptions/ folder that implements StructuredException, with a named
 * constructor per failure. PHP's own, such as `new LogicException('...')`, carry only a message.
 *
 * @implements Rule<New_>
 */
final class StructuredExceptionRule implements Rule
{
    public function getNodeType(): string
    {
        return New_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $type = $scope->getType($node);
        $classes = $type->getObjectClassReflections();
        if ($classes === []
            || ! (new ObjectType(Throwable::class))->isSuperTypeOf($type)->yes()
            || (new ObjectType(StructuredException::class))->isSuperTypeOf($type)->yes()) {
            return [];
        }

        // Framework exceptions such as AuthenticationException are turned into responses by
        // Laravel, so only PHP's own, which carry nothing but a message, are reported.
        if (array_filter($classes, fn ($class) => ! $class->isBuiltin()) !== []) {
            return [];
        }

        $class = implode(', ', $type->getObjectClassNames());

        return [
            RuleErrorBuilder::message("Throw a structured exception instead of {$class}.")
                ->identifier('larasell.structuredException')
                ->tip("Add a named constructor to the area's exception in Exceptions/, or throw StructuredLogicException inline for a check of the panel's own code.")
                ->build(),
        ];
    }
}
