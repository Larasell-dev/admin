<?php

namespace Larasell\Admin\Lint\PHPStan;

use PHPStan\Node\Printer\Printer;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<PreferRouteHelperRule>
 */
final class PreferRouteHelperRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new PreferRouteHelperRule(self::getContainer()->getByType(Printer::class));
    }

    public function test_it_reports_hard_coded_urls(): void
    {
        $tip = 'If the path has no named route on purpose, such as a 404 check, add // @phpstan-ignore larasell.preferRouteHelper';
        $error = fn (string $url, int $line) => ["Use route() instead of the hard-coded URL {$url}.", $line, $tip];

        $this->analyse([__DIR__.'/data/prefer-route-helper.php'], [
            $error("'/admin/login'", 15),
            $error("'/admin/products'", 16),
            $error("'/admin/profile'", 17),
            $error("'/admin/categories/1'", 18),
            $error("'/admin/categories/1/available-products'", 19),
            $error("'/admin/profile/avatar'", 20),
            $error('"/admin/products/{$category->id}/edit"', 23),
            $error('"/admin/categories/create?parent={$category->id}"', 26),
            $error("'/admin/profile?id=' . \$category->id", 29),
            $error("'/admin/profile'", 32),
            $error("'/admin/profile'", 32),
            $error("'/admin'", 35),
            $error("'/admin/login'", 35),
            $error("'/admin/categories'", 36),
            $error('"/admin/categories/{$category->id}"', 36),
            $error("'/admin/categories/create'", 39),
            $error('"/admin/categories/{$category->id}"', 40),
            $error("'/admin/customers'", 44),
            $error("'/admin/login'", 67),
            $error("'/admin'", 68),
        ]);
    }
}
