<?php

declare(strict_types=1);

/*
 * The panel is a modular monolith. The platform (Larasell\Admin\Platform) is what modules and
 * apps plug into, so it never uses a module. A module owns one feature and uses the platform,
 * plus only the other modules listed here as what it builds on.
 */
const MODULE_DEPENDENCIES = [
    'ApiToken' => [],
    'Category' => [],
    // The customer page lists the customer's orders with the Order module's table.
    'Customer' => ['Order'],
    'Order' => [],
    // The product form picks categories with the Category module's options.
    'Product' => ['Category'],
    'SalesChannel' => [],
    // The URLs sections are sections of the Product and Category modules' pages.
    'Seo' => ['Product', 'Category'],
    'Team' => [],
];

/**
 * @param  list<string>  $modules
 * @return list<string>
 */
function moduleNamespaces(array $modules): array
{
    return array_map(fn (string $module): string => 'Larasell\\Admin\\'.$module, $modules);
}

arch('the platform uses no module')
    ->expect('Larasell\Admin\Platform')
    ->not->toUse(moduleNamespaces(array_keys(MODULE_DEPENDENCIES)));

foreach (MODULE_DEPENDENCIES as $module => $buildsOn) {
    $others = array_values(array_diff(array_keys(MODULE_DEPENDENCIES), [$module], $buildsOn));

    arch("the {$module} module uses no other module than it builds on")
        ->expect('Larasell\Admin\\'.$module)
        ->not->toUse(moduleNamespaces($others));
}

arch('only the admin service provider registers the modules')
    ->expect(moduleNamespaces(array_map(fn (string $module): string => "{$module}\\{$module}ServiceProvider", array_keys(MODULE_DEPENDENCIES))))
    ->toOnlyBeUsedIn('Larasell\Admin\AdminServiceProvider');
