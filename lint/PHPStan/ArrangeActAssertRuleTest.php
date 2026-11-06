<?php

namespace Larasell\Admin\Lint\PHPStan;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ArrangeActAssertRule>
 */
final class ArrangeActAssertRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ArrangeActAssertRule;
    }

    public function test_it_reports_tests_without_arrange_act_assert_sections(): void
    {
        $tip = 'Leave out // Arrange when the test sets nothing up, and combine sections that can\'t be split, such as // Act & Assert.';
        $error = fn (string $found, int $line) => ["Mark this test's sections with // Arrange, // Act and // Assert, in that order. Found: {$found}.", $line, $tip];

        $this->analyse([__DIR__.'/data/arrange-act-assert.php'], [
            $error('none', 6),
            $error('// Arrange, // Act', 10),
            $error('// Act, // Arrange, // Assert', 18),
            $error('// Act, // Act, // Assert', 29),
            $error('// Arrange & Assert', 40),
            ['Start this test with its // Act comment: the code above it belongs to no section.', 46],
            $error('none', 55),
            $error('none', 63),
        ]);
    }
}
