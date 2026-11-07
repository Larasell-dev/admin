<?php

declare(strict_types=1);

/*
 * The panel is a modular monolith. The platform (Larasell\Admin\Platform) is what modules and
 * apps plug into, so it never uses a module. A module owns one feature and uses the platform,
 * plus only the other modules it builds on. lint/modules.json lists those, and why, for this test
 * and the frontend's module-boundaries lint rule alike.
 */
define('MODULE_DEPENDENCIES', array_map(
    fn (array $module): array => $module['buildsOn'],
    json_decode((string) file_get_contents(__DIR__.'/../../lint/modules.json'), true, flags: JSON_THROW_ON_ERROR),
));

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
