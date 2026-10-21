# Changelog

## [0.3.2](https://github.com/Larasell-dev/admin/compare/v0.3.1...v0.3.2) (2026-10-20)


### Bug Fixes

* keep the table's action column at the right edge ([#85](https://github.com/Larasell-dev/admin/issues/85)) ([96da6fe](https://github.com/Larasell-dev/admin/commit/96da6fe6412aee1223bea0ce961cd0b6e9b23e48))


### Miscellaneous Chores

* update @larasell-dev/ui to 0.1.14 ([#87](https://github.com/Larasell-dev/admin/issues/87)) ([4640b64](https://github.com/Larasell-dev/admin/commit/4640b6414a739f8bacec0645bb4212ee75ce1ac0))

## [0.3.1](https://github.com/Larasell-dev/admin/compare/v0.3.0...v0.3.1) (2026-10-20)


### Bug Fixes

* let map() callbacks type the record as the table's model ([#83](https://github.com/Larasell-dev/admin/issues/83)) ([5af24e9](https://github.com/Larasell-dev/admin/commit/5af24e90fc96d7137ff4e0d82e57698118d5f2c2))

## [0.3.0](https://github.com/Larasell-dev/admin/compare/v0.2.0...v0.3.0) (2026-10-20)


### ⚠ BREAKING CHANGES

* reject configuring a table column or action twice ([#77](https://github.com/Larasell-dev/admin/issues/77))

### Features

* compute table column values with map() ([#75](https://github.com/Larasell-dev/admin/issues/75)) ([036c505](https://github.com/Larasell-dev/admin/commit/036c5058d12bc3a68772479f2bdc1cffd2530cb3))
* let a table list the records of a query or relationship ([#82](https://github.com/Larasell-dev/admin/issues/82)) ([90b33f9](https://github.com/Larasell-dev/admin/commit/90b33f902742c0fc4be94a1d6f5172436bb4caf3))
* reject a mapped text column value that is not text ([#78](https://github.com/Larasell-dev/admin/issues/78)) ([05010ef](https://github.com/Larasell-dev/admin/commit/05010ef59d795b53c5376ca738367c8d418aab00))
* reject configuring a table column or action twice ([#77](https://github.com/Larasell-dev/admin/issues/77)) ([b2775de](https://github.com/Larasell-dev/admin/commit/b2775de89297ad9b57fcc06369a2c0c56bb790ba))

### Upgrade guide

#### Action configuration methods can only be called once

Calling `to()`, `route()`, `confirm()`, `destructive()`, `disabled()` or
`icon()` a second time on the same action used to silently replace the first
call. It now throws an `InvalidTableAction` exception with the code
`larasell-admin.table.action.configured-twice`, so a call that has no effect
can't hide in a table definition.

##### Who is affected

Tables that call one of these methods more than once on the same
`Larasell\Admin\Tables\Actions\Action`, including `to()` followed by `route()`,
since both set the link. If every method is called at most once per action,
you can skip this entry.

##### How to upgrade

Keep only the call you want to take effect, usually the last one:

```php
use Larasell\Admin\Tables\Actions\Action;

// Before
Action::make('Delete', handle: fn (Book $book) => $book->delete())
    ->disabled()
    ->disabled(fn (Book $book) => $book->is_locked);

// After
Action::make('Delete', handle: fn (Book $book) => $book->delete())
    ->disabled(fn (Book $book) => $book->is_locked);
```

#### Table column names must be unique and not reserved

A table whose columns share a name, or that has a column named `_actions` or
`_link`, now throws an `InvalidTableColumn` exception when it renders. Each row
stores a column's value under its name, so such a column silently overwrote
another column's value or the row's actions or link.

##### Who is affected

Tables whose `columns()` return two columns with the same name, or a column
named `_actions` or `_link`. Tables with unique column names that don't start
with an underscore are not affected. An `id` column is still allowed.

##### How to upgrade

Give the second column its own name and read the attribute in `map()`:

```php
use Larasell\Admin\Tables\Columns\TextColumn;

// Before
TextColumn::make('created_at', 'Received')->map(fn ($value) => $value?->diffForHumans()),
TextColumn::make('created_at', 'Date'),

// After
TextColumn::make('received')->map(fn ($value, ContactRequest $request) => $request->created_at?->diffForHumans()),
TextColumn::make('created_at', 'Date'),
```

## [0.2.0](https://github.com/Larasell-dev/admin/compare/v0.1.9...v0.2.0) (2026-10-20)


### ⚠ BREAKING CHANGES

* validate icons the same way everywhere ([#70](https://github.com/Larasell-dev/admin/issues/70))
* Larasell\Admin\Enums\NavIcon is now Larasell\Admin\Enums\Icon. Cases and values are unchanged; replace NavIcon imports and references with Icon.

### Features

* link table rows with rowLink() ([#67](https://github.com/Larasell-dev/admin/issues/67)) ([148d467](https://github.com/Larasell-dev/admin/commit/148d46754bf4d0a4869c463f71885b9d8db1ae11))
* paginate tables ([#72](https://github.com/Larasell-dev/admin/issues/72)) ([15520d9](https://github.com/Larasell-dev/admin/commit/15520d9b0edd224e640f775c4bda343a433a1e6c))
* show icons on table actions with icon() ([#69](https://github.com/Larasell-dev/admin/issues/69)) ([373fdf6](https://github.com/Larasell-dev/admin/commit/373fdf688176c8158c1e04174527570de6079597))


### Bug Fixes

* rename NavIcon enum to Icon ([#63](https://github.com/Larasell-dev/admin/issues/63)) ([9f4c87a](https://github.com/Larasell-dev/admin/commit/9f4c87a1fbefebefb90b863af55b4efbb26e0bd9))
* stop tables from overriding query() ([#71](https://github.com/Larasell-dev/admin/issues/71)) ([d1dff3e](https://github.com/Larasell-dev/admin/commit/d1dff3e8bb5903dce9e25f8f7c24ddbbd027dce9))
* validate icons the same way everywhere ([#70](https://github.com/Larasell-dev/admin/issues/70)) ([f26235e](https://github.com/Larasell-dev/admin/commit/f26235eca0da0c353a9ea1767857f09eda622ad3))

### Upgrade guide

#### An empty empty-state icon now throws `empty-icon`

A table whose `emptyState()` returns an empty or whitespace-only `'icon'` now
fails with the error code `larasell-admin.table.empty-state.empty-icon` instead
of `larasell-admin.table.empty-state.unknown-icon`. A blank icon is a different
mistake than a misspelled one, so it now gets its own error and fix, the same
way navigation items and table actions already report it.

##### Who is affected

Apps that catch `Larasell\Admin\Exceptions\InvalidTableEmptyState` and compare
its `errorCode()` to `larasell-admin.table.empty-state.unknown-icon` for blank
icons. The exception class is unchanged, and so is the error for icon names
that are not empty. Apps that don't inspect error codes are not affected.

##### How to upgrade

Check for the new code where you handle blank icons:

```php
use Larasell\Admin\Exceptions\InvalidTableEmptyState;

// Before
if ($exception->errorCode() === 'larasell-admin.table.empty-state.unknown-icon') {
    // ...
}

// After
if (in_array($exception->errorCode(), [
    'larasell-admin.table.empty-state.empty-icon',
    'larasell-admin.table.empty-state.unknown-icon',
], true)) {
    // ...
}
```

To avoid the error, leave `'icon'` out of `emptyState()` for the default icon
instead of returning `''`.

#### The `NavIcon` enum is now `Icon`

`Larasell\Admin\Enums\NavIcon` is renamed to `Larasell\Admin\Enums\Icon`. The
enum is no longer only for navigation items: tables use it for their empty
state too, so the old name was misleading. Its cases and values are unchanged.

##### Who is affected

Apps that import `Larasell\Admin\Enums\NavIcon`, for example to register
navigation items or to set a table's empty state icon. Apps that pass icons as
strings such as `'home'` are not affected.

##### How to upgrade

Replace every `NavIcon` import and reference with `Icon`:

```php
// Before
use Larasell\Admin\Enums\NavIcon;

Navigation::add('Orders', 'orders.index', NavIcon::ShoppingBag);

// After
use Larasell\Admin\Enums\Icon;

Navigation::add('Orders', 'orders.index', Icon::ShoppingBag);
```

Cases and their string values stay the same, so a find-and-replace of
`Enums\NavIcon` and `NavIcon::` is enough.

## [0.1.9](https://github.com/Larasell-dev/admin/compare/v0.1.8...v0.1.9) (2026-10-19)


### Features

* add make:larasell-table command ([#58](https://github.com/Larasell-dev/admin/issues/58)) ([066fac0](https://github.com/Larasell-dev/admin/commit/066fac0d5f516ef408cf04a49b04ed344c81f5a1))
* add PHP-defined admin tables with Route::index() ([#57](https://github.com/Larasell-dev/admin/issues/57)) ([c8b9fe7](https://github.com/Larasell-dev/admin/commit/c8b9fe7cf2475a88e4eefe5dc22454c91c7d7d06))
* add roving focus to sidebar navigation ([#53](https://github.com/Larasell-dev/admin/issues/53)) ([68bb688](https://github.com/Larasell-dev/admin/commit/68bb688d4c2b23e59cfc9ef822e1974b939c2cbe))
* add row actions to admin tables ([#60](https://github.com/Larasell-dev/admin/issues/60)) ([2741a6f](https://github.com/Larasell-dev/admin/commit/2741a6f8ff7aff174264bedd9ae3db226daa8741))
* declare a table's model with the UseModel attribute ([#62](https://github.com/Larasell-dev/admin/issues/62)) ([039cbba](https://github.com/Larasell-dev/admin/commit/039cbba4b7251ee709181be8ab26c6597501a33b))
* generate Inertia page prop types with laravel-data ([#56](https://github.com/Larasell-dev/admin/issues/56)) ([0fb5e78](https://github.com/Larasell-dev/admin/commit/0fb5e781364c4f386e854753b4c55a4e2891debd))
* let tables define their empty state ([#61](https://github.com/Larasell-dev/admin/issues/61)) ([72fffda](https://github.com/Larasell-dev/admin/commit/72fffdab838f7a7009433753ae36f293b55f4db3))


### Miscellaneous Chores

* add oxfmt formatter and check formatting in CI ([#52](https://github.com/Larasell-dev/admin/issues/52)) ([0327723](https://github.com/Larasell-dev/admin/commit/0327723e03286dd3a8118ec1060dc687c30fd723))

## [0.1.8](https://github.com/Larasell-dev/admin/compare/v0.1.7...v0.1.8) (2026-10-18)


### Features

* add customers index page ([#50](https://github.com/Larasell-dev/admin/issues/50)) ([1dbf7dd](https://github.com/Larasell-dev/admin/commit/1dbf7dd77cd8fd9ae4226c50bdf9efbcf5011e71))
* add local-only __dev endpoints for login, logout and route listing ([#48](https://github.com/Larasell-dev/admin/issues/48)) ([fa08c17](https://github.com/Larasell-dev/admin/commit/fa08c172f1e4c1d2efaf660e43f4018049c85173))

## [0.1.7](https://github.com/Larasell-dev/admin/compare/v0.1.6...v0.1.7) (2026-10-18)


### Features

* add a command to list admins ([#44](https://github.com/Larasell-dev/admin/issues/44)) ([c294e51](https://github.com/Larasell-dev/admin/commit/c294e5106c54ceb0153852a934883cfd3ae5d197))
* add a command to remove admins ([#45](https://github.com/Larasell-dev/admin/issues/45)) ([127c474](https://github.com/Larasell-dev/admin/commit/127c474d9fadbf13ceec522482e46f4121d6b1a6))
* add skip to main content link ([#47](https://github.com/Larasell-dev/admin/issues/47)) ([34a5e7d](https://github.com/Larasell-dev/admin/commit/34a5e7d20b55075bf007c9ab7d84a009b97f48e6))

## [0.1.6](https://github.com/Larasell-dev/admin/compare/v0.1.5...v0.1.6) (2026-10-18)


### Features

* follow the system color scheme ([#42](https://github.com/Larasell-dev/admin/issues/42)) ([f13a65f](https://github.com/Larasell-dev/admin/commit/f13a65fced31a9113bd2612a338c1f71c4fd98d4))
* use the ui border color token ([#40](https://github.com/Larasell-dev/admin/issues/40)) ([86be8de](https://github.com/Larasell-dev/admin/commit/86be8dec4c0fa8ae8bfff224e63eb69091e75f16))

## [0.1.5](https://github.com/Larasell-dev/admin/compare/v0.1.4...v0.1.5) (2026-10-17)


### Features

* enforce strong passwords when creating an admin ([#37](https://github.com/Larasell-dev/admin/issues/37)) ([6d186dd](https://github.com/Larasell-dev/admin/commit/6d186ddfb1f3830119f50af461c8b06ec03d335f))
* require a validated icon on every navigation item ([#38](https://github.com/Larasell-dev/admin/issues/38)) ([f3f58f6](https://github.com/Larasell-dev/admin/commit/f3f58f6059fe47830833238ea1ba5453590c8f99))


### Bug Fixes

* show clear error when creating an admin with an existing email ([#35](https://github.com/Larasell-dev/admin/issues/35)) ([cc19ccb](https://github.com/Larasell-dev/admin/commit/cc19ccb39903fba72f66e89400816b18b420a8ec))

## [0.1.4](https://github.com/Larasell-dev/admin/compare/v0.1.3...v0.1.4) (2026-10-17)


### Features

* add positioning api to the navigation facade ([#33](https://github.com/Larasell-dev/admin/issues/33)) ([41b3d24](https://github.com/Larasell-dev/admin/commit/41b3d24c8adce458ae0adee8777ba5f9253275ba))


### Bug Fixes

* keep the mobile nav padding constant across breakpoints ([#31](https://github.com/Larasell-dev/admin/issues/31)) ([5065760](https://github.com/Larasell-dev/admin/commit/5065760d0284e3299efd9cce9b261361324d250c))


### Miscellaneous Chores

* update @larasell-dev/ui to 0.1.9 ([#34](https://github.com/Larasell-dev/admin/issues/34)) ([cdde471](https://github.com/Larasell-dev/admin/commit/cdde471bf29a91807830b35442c4aba8f0b11236))

## [0.1.3](https://github.com/Larasell-dev/admin/compare/v0.1.2...v0.1.3) (2026-10-17)


### Features

* ship types for the admin page modules ([#29](https://github.com/Larasell-dev/admin/issues/29)) ([ae5a2dd](https://github.com/Larasell-dev/admin/commit/ae5a2ddf322719f8de2717f9e9aee860d9517093))

## [0.1.2](https://github.com/Larasell-dev/admin/compare/v0.1.1...v0.1.2) (2026-10-17)


### Features

* expose @larasell-dev/ui for external admin pages ([#26](https://github.com/Larasell-dev/admin/issues/26)) ([61d537f](https://github.com/Larasell-dev/admin/commit/61d537fbcbf32e9fc53193be5f48b74eec882b70))


### Bug Fixes

* match the sidebar footer height to the table pagination ([#27](https://github.com/Larasell-dev/admin/issues/27)) ([58fb5f9](https://github.com/Larasell-dev/admin/commit/58fb5f935047860795421683136f47b931a15300))

## [0.1.1](https://github.com/Larasell-dev/admin/compare/v0.1.0...v0.1.1) (2026-10-17)


### Features

* expose SidebarLayout for external admin pages ([#24](https://github.com/Larasell-dev/admin/issues/24)) ([007428e](https://github.com/Larasell-dev/admin/commit/007428e13a358d98f1d0fd5058517711a1b17511))

## 0.1.0 (2026-10-16)


### Features

* add a sidebar layout to the admin panel ([#15](https://github.com/Larasell-dev/admin/issues/15)) ([cc6dd88](https://github.com/Larasell-dev/admin/commit/cc6dd88cdd1d351a06324f32934416e902778da4))
* add admin panel ([#1](https://github.com/Larasell-dev/admin/issues/1)) ([629f029](https://github.com/Larasell-dev/admin/commit/629f02954e00b6d96a35418f35be02b09c41d30b))
* add mobile header with off-canvas navigation ([#21](https://github.com/Larasell-dev/admin/issues/21)) ([804f955](https://github.com/Larasell-dev/admin/commit/804f9554e65233fcf1451b021559e488594fdbac))
* add profile page to update name and email ([e8e1624](https://github.com/Larasell-dev/admin/commit/e8e1624f9a9f8e24fc8a9ddee225eedb27dde34b))
* allow admins to upload a profile picture ([#19](https://github.com/Larasell-dev/admin/issues/19)) ([9f55158](https://github.com/Larasell-dev/admin/commit/9f55158c39c590f07529946251f58f0dab5c9b9e))
* replace sidebar logout button with a user menu ([#22](https://github.com/Larasell-dev/admin/issues/22)) ([41ba0dd](https://github.com/Larasell-dev/admin/commit/41ba0dda79090b3d625634586b38f5cb72db50af))
* style the login page with @larasell-dev/ui ([#8](https://github.com/Larasell-dev/admin/issues/8)) ([3282e4d](https://github.com/Larasell-dev/admin/commit/3282e4d05f3eab1f979625955736471e30bdb3f7))
* use fullWidth button prop on login page ([0248b00](https://github.com/Larasell-dev/admin/commit/0248b0049185d9d51b29f6c67adad19c5ac90f6e))
* use ui progress color token for the Inertia loader ([#11](https://github.com/Larasell-dev/admin/issues/11)) ([6c9faf1](https://github.com/Larasell-dev/admin/commit/6c9faf1fcd0d3db3e970a2a38811c645a726d351))


### Bug Fixes

* match website backdrop and align mobile header with menu ([#23](https://github.com/Larasell-dev/admin/issues/23)) ([22772f0](https://github.com/Larasell-dev/admin/commit/22772f0a814f68f79edac8b4a00cc3fd4186c602))
* share validation errors with admin pages ([19acbd3](https://github.com/Larasell-dev/admin/commit/19acbd3f2aab997bad5adc3be7ad59cfacc66dc1))
* type the Inertia page resolver ([d525f39](https://github.com/Larasell-dev/admin/commit/d525f39fd37d09a408156b7a2939170ad3fe00da))


### Miscellaneous Chores

* add Amp orb setup script ([954fc15](https://github.com/Larasell-dev/admin/commit/954fc158ecb038bd2000535d6e948c1b696f981d))
* add pint ([#2](https://github.com/Larasell-dev/admin/issues/2)) ([04e37fe](https://github.com/Larasell-dev/admin/commit/04e37fe5c3adcc7eed4b7d6af393c9405f613d62))
* add test ([6359f6e](https://github.com/Larasell-dev/admin/commit/6359f6ee5962cea53b537dbc8dfb9a755ec7d0e7))
* ban commit scopes and drop scope from release PR title ([49079eb](https://github.com/Larasell-dev/admin/commit/49079ebdda5a259ef6ff5021725bc800c6a18a84))
* **deps:** upgrade @larasell-dev/ui to 0.1.5 ([0a522f2](https://github.com/Larasell-dev/admin/commit/0a522f221c69d595262e838e70021b2e27ea0025))
* improve errors for NavigationItem ([d1f8746](https://github.com/Larasell-dev/admin/commit/d1f874650035321943ace2a0034b2a1b8d000532))
* initial commit ([1eb9a74](https://github.com/Larasell-dev/admin/commit/1eb9a74feb642ae11f5e717b03cddbba0a64b50a))
* serve the admin panel in Amp orbs over HTTPS ([#14](https://github.com/Larasell-dev/admin/issues/14)) ([c6c6b12](https://github.com/Larasell-dev/admin/commit/c6c6b120957f0be49dd48e144f12a733fe5511ba))
