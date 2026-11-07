<?php

namespace Larasell\Admin\Lint\PHPStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<StructuredExceptionRule>
 */
final class StructuredExceptionRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new StructuredExceptionRule;
    }

    public function test_it_reports_plain_exceptions(): void
    {
        // Arrange
        $tip = "Add a named constructor to the area's exception in Exceptions/, or throw StructuredLogicException inline for a check of the panel's own code.";

        // Act & Assert
        $this->analyse([__DIR__.'/data/structured-exception.php'], [
            ['Throw a structured exception instead of LogicException.', 8, $tip],
            ['Throw a structured exception instead of RuntimeException.', 13, $tip],
        ]);
    }
}
