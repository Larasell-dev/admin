# Changelog

## [0.5.5](https://github.com/Larasell-dev/admin/compare/v0.5.4...v0.5.5) (2026-10-31)


### Bug Fixes

* show the API tokens, orders and customers tables in apps with immutable dates ([1d8094f](https://github.com/Larasell-dev/admin/commit/1d8094f0e06b84e7e06312f76b99c83342cb5104))


### Miscellaneous Chores

* add an oxlint rule that keeps font sizes on the typescale ([9a711d0](https://github.com/Larasell-dev/admin/commit/9a711d03f1562015cba1be0271b3bb31fe188b81))

## [0.5.4](https://github.com/Larasell-dev/admin/compare/v0.5.3...v0.5.4) (2026-10-31)


### Bug Fixes

* authenticate API tokens before route bindings and throttling ([deea5cf](https://github.com/Larasell-dev/admin/commit/deea5cf7c5e58c7d0daeecca9959a24f25990fbc))

## [0.5.3](https://github.com/Larasell-dev/admin/compare/v0.5.2...v0.5.3) (2026-10-31)


### Features

* add API tokens for extension APIs ([da61c78](https://github.com/Larasell-dev/admin/commit/da61c781ba8bf3b0579bf6ccb2838cb32d7f1b30))
* let apps turn the API tokens module off ([7912788](https://github.com/Larasell-dev/admin/commit/791278865f575ccc84cbe363b90c7403804891e7))
* limit failed API token attempts per address ([85c2ea5](https://github.com/Larasell-dev/admin/commit/85c2ea5a1a2ee1eea52f87f769d8b557ad116abb))
* show a toast when the theme changes ([#198](https://github.com/Larasell-dev/admin/issues/198)) ([7308cf8](https://github.com/Larasell-dev/admin/commit/7308cf82dd94819a6f173dd9124bbc516d62e987))

## [0.5.2](https://github.com/Larasell-dev/admin/compare/v0.5.1...v0.5.2) (2026-10-30)


### Bug Fixes

* register Navigation::index routes once when called from routes/admin.php ([#194](https://github.com/Larasell-dev/admin/issues/194)) ([89f5161](https://github.com/Larasell-dev/admin/commit/89f51616e09ff6d4837870de5adbf075228712a3))

## [0.5.1](https://github.com/Larasell-dev/admin/compare/v0.5.0...v0.5.1) (2026-10-29)


### Bug Fixes

* keep the app's own error response callback alongside the admin 404 page ([#192](https://github.com/Larasell-dev/admin/issues/192)) ([10f4357](https://github.com/Larasell-dev/admin/commit/10f43571b14e3f37523dd2a032ca2e7ef6b7c305))

## [0.5.0](https://github.com/Larasell-dev/admin/compare/v0.4.1...v0.5.0) (2026-10-29)


### ⚠ BREAKING CHANGES

* let extensions register admin pages through @larasell-dev/admin ([#191](https://github.com/Larasell-dev/admin/issues/191))
* add an orders index page
* list products with Route::index and renumber the navigation
* make the admin config publishable ([#151](https://github.com/Larasell-dev/admin/issues/151))

### Features

* add a read-only order page ([446ac8f](https://github.com/Larasell-dev/admin/commit/446ac8f8ee109faa6d77fc500feb06ee7f4f13e8))
* add admins from the settings ([6fa3043](https://github.com/Larasell-dev/admin/commit/6fa304318ee8ba0f1a4534392df473161a837596))
* add an orders index page ([e511842](https://github.com/Larasell-dev/admin/commit/e5118424141b540325530ad0d08e81684f4fb83a))
* add and edit customers ([b4fb93a](https://github.com/Larasell-dev/admin/commit/b4fb93a2a195d25bda3d7c39cfa0d604ca703ae2))
* add table actions to table headers and empty states ([#156](https://github.com/Larasell-dev/admin/issues/156)) ([18f8539](https://github.com/Larasell-dev/admin/commit/18f853910bfc3bb795f44e5b11483015381ae833))
* ask for the password again once the session expires ([a412ac5](https://github.com/Larasell-dev/admin/commit/a412ac5ceae264cf7d9d2fe8da34445b63d75673))
* create and edit products ([#157](https://github.com/Larasell-dev/admin/issues/157)) ([43772d6](https://github.com/Larasell-dev/admin/commit/43772d68355b8384a003b88eb2e290673ff2b9ad))
* cycle the theme with a rebindable keyboard shortcut ([#145](https://github.com/Larasell-dev/admin/issues/145)) ([e377c01](https://github.com/Larasell-dev/admin/commit/e377c011bae1473a9ac01a8c5b19fcf0511b55a5))
* delete admins from the admins table ([4814085](https://github.com/Larasell-dev/admin/commit/4814085b405ea9239420924937f2f3dace9d3a15))
* delete customers from the customers table ([e8c45a9](https://github.com/Larasell-dev/admin/commit/e8c45a91be5eab522ef5d7a78bcc8c305ca2d3aa))
* delete products from the products table ([1f1a318](https://github.com/Larasell-dev/admin/commit/1f1a318f61d091caa1db80f0a9e11faa2c223e9b))
* edit a product's SKU and barcode ([#159](https://github.com/Larasell-dev/admin/issues/159)) ([59d738e](https://github.com/Larasell-dev/admin/commit/59d738e9f2c7576d686485836863f1f0000f6609))
* edit a product's stock, backorders and quantity limits ([eb3c45e](https://github.com/Larasell-dev/admin/commit/eb3c45e06ba0dcdfe56e313986dafb8b619079cf))
* edit a product's weight and dimensions ([d1004df](https://github.com/Larasell-dev/admin/commit/d1004df787e15519156c0b3a9e4b4f6e536f749e))
* edit admins from the settings ([47206f6](https://github.com/Larasell-dev/admin/commit/47206f66b4e4a81f22b845ccb49672ec9ac4a5b1))
* fill a new category's slug from its name ([#118](https://github.com/Larasell-dev/admin/issues/118)) ([e61875f](https://github.com/Larasell-dev/admin/commit/e61875f2fe8a2d62c58871e3892f13a74e07a87d))
* freeze the navigation once the app has booted ([#134](https://github.com/Larasell-dev/admin/issues/134)) ([9c51714](https://github.com/Larasell-dev/admin/commit/9c517149036e577a3c2de0ea940b3c0fe754c856))
* hide a row action, for every row or per record ([0f541cc](https://github.com/Larasell-dev/admin/commit/0f541cc55996f6f444429501737567aab5f4edb6))
* hold off saving while a field in the form is busy ([dddf955](https://github.com/Larasell-dev/admin/commit/dddf95599d70e6224683bf1f515fce36c0ed2459))
* let admins choose a light, dark, or system theme on the profile page ([87b016f](https://github.com/Larasell-dev/admin/commit/87b016f5dca87499fca86ae18fb7af0fba365fb6))
* let apps turn the customers module off ([2190ec5](https://github.com/Larasell-dev/admin/commit/2190ec576236634401f27e537b13371d89c291b8))
* let extensions register admin pages through @larasell-dev/admin ([#191](https://github.com/Larasell-dev/admin/issues/191)) ([f3ce5c7](https://github.com/Larasell-dev/admin/commit/f3ce5c7094bb823f0018cc71d0bd896de7c7dff1))
* limit navigation items to 100 children per parent ([#132](https://github.com/Larasell-dev/admin/issues/132)) ([f7a4f57](https://github.com/Larasell-dev/admin/commit/f7a4f574d1b195bbc05e952dec9d85c29829c885))
* link the dashboard to the first onboarding steps ([672ad5b](https://github.com/Larasell-dev/admin/commit/672ad5be76bf2071d5da11ea06fb14aa0a967f3c))
* link the products on a category's page to their edit page ([768b640](https://github.com/Larasell-dev/admin/commit/768b640e2c6de30433174af9dcc6e6b1aa565a79))
* link to creating a product from a category's empty products ([af39a9b](https://github.com/Larasell-dev/admin/commit/af39a9bffa2375acdc6e02d4a3bb844317b816ab))
* list a customer's orders on their page ([003a162](https://github.com/Larasell-dev/admin/commit/003a1623013a0fa30f35f42a7026a92746261529))
* list admins under a new Settings navigation item ([dc0fed7](https://github.com/Larasell-dev/admin/commit/dc0fed78a25bb9dfa43295a9d7699bff98398ef5))
* list products on a new Products page ([5cbda18](https://github.com/Larasell-dev/admin/commit/5cbda184ab99423093064e8823ba44ca4bd19a65))
* list products with Route::index and renumber the navigation ([ec98739](https://github.com/Larasell-dev/admin/commit/ec987395aae4d2545126e95666e7405f1f0820de))
* make the admin config publishable ([#151](https://github.com/Larasell-dev/admin/issues/151)) ([41d12de](https://github.com/Larasell-dev/admin/commit/41d12de8aed742d98ec0dc46750bfd426f8f6cee))
* open a product's images in a lightbox to edit their alt text ([85ca66e](https://github.com/Larasell-dev/admin/commit/85ca66eba325830b6f3f07e18ca4d2a6794813e3))
* pick a product's categories from a checkbox tree ([b07a0b1](https://github.com/Larasell-dev/admin/commit/b07a0b17032db2f31ae6ecb28bfacb863b976ea8))
* prefetch link actions in a category's products menu on hover ([a90804d](https://github.com/Larasell-dev/admin/commit/a90804d1b9aa74474ccd93286a291c84cda2c127))
* put the profile fields in a General section ([2350974](https://github.com/Larasell-dev/admin/commit/2350974161a0f453c54da5703a2e1984450c3386))
* register a table route and its navigation item with Navigation::index ([87367c9](https://github.com/Larasell-dev/admin/commit/87367c9ab22e2d7de9b54278be532223f604db67))
* remove a product's images from a menu on each image ([70e9969](https://github.com/Larasell-dev/admin/commit/70e9969f7851e6861cae89c16661831981d21996))
* render the admin 404 pages through Inertia's exception handling ([1ccf765](https://github.com/Larasell-dev/admin/commit/1ccf76550594822988a12fa8680620b0422006fd))
* reorder a product's images by dragging them ([27fda6a](https://github.com/Larasell-dev/admin/commit/27fda6a71806a297a5331247f6a838ef5b8e42b2))
* report navigation items with an unregistered route as a structured error ([#135](https://github.com/Larasell-dev/admin/issues/135)) ([7ed7c40](https://github.com/Larasell-dev/admin/commit/7ed7c40d2340a250699de242c6d4456337a67b28))
* shorten the category products menu's view item to "View" ([b402a0a](https://github.com/Larasell-dev/admin/commit/b402a0a01b1e941c41171d706ecceef955e262f0))
* shorten the category tree menu's view item to "View" ([96c6bb8](https://github.com/Larasell-dev/admin/commit/96c6bb811b77245a70f01bafc813ae3bb4c8c4c1))
* show a 404 page inside the admin panel ([6cb9000](https://github.com/Larasell-dev/admin/commit/6cb900069bad559efbab51703cfd4d3632cce5da))
* show a loading card per image while it uploads ([3291b48](https://github.com/Larasell-dev/admin/commit/3291b48d53b2faa7f34e127c2c3bd4b599baede2))
* show a loading state on the dev login link ([#126](https://github.com/Larasell-dev/admin/issues/126)) ([1cda394](https://github.com/Larasell-dev/admin/commit/1cda394f9bd22fe430572cdf2d7601be5a291762))
* show an icon or picture in table cells with image() ([#150](https://github.com/Larasell-dev/admin/issues/150)) ([b16c152](https://github.com/Larasell-dev/admin/commit/b16c152c4a9c2cabd961140b5a8952a6ddbcc8ec))
* show Products above Categories in the navigation ([27270eb](https://github.com/Larasell-dev/admin/commit/27270ebb2142e0cecbc869f97fa78c944f803721))
* show toasts and say why a stale image link didn't open ([ff15aea](https://github.com/Larasell-dev/admin/commit/ff15aea1824fe86b31cf2ab52ec11ea9d3c07463))
* show values in a coloured badge with BadgeColumn ([#152](https://github.com/Larasell-dev/admin/issues/152)) ([96cfc25](https://github.com/Larasell-dev/admin/commit/96cfc255c8eef3da23cbb11316addf9b90cc8cc2))
* support nested navigation items ([#128](https://github.com/Larasell-dev/admin/issues/128)) ([efb1f9e](https://github.com/Larasell-dev/admin/commit/efb1f9e6d9bcae0e31d1737a602bef200037e3f1))
* upload a product's images and link them on save ([24f48f2](https://github.com/Larasell-dev/admin/commit/24f48f26f6feb1a8b387a3a4501bc1a9a4595da8))
* use PasswordInput for the login password field ([#124](https://github.com/Larasell-dev/admin/issues/124)) ([455715d](https://github.com/Larasell-dev/admin/commit/455715d5b46699c6143fc5dd7c692a3a0bac97e5))
* use the unsaved changes toast on the profile page ([#125](https://github.com/Larasell-dev/admin/issues/125)) ([c24acf7](https://github.com/Larasell-dev/admin/commit/c24acf73ff46aae78dba59ecc5135bf6cb6e5084))
* view a product from a category's products menu ([ced44be](https://github.com/Larasell-dev/admin/commit/ced44bebfc98da13abd96b3e9012a136ab840146))


### Bug Fixes

* animate the session expired dialog out after signing back in ([ebc1bb4](https://github.com/Larasell-dev/admin/commit/ebc1bb48515e3b7994b4ea10000746cd8e0171f7))
* break navigation position ties by id instead of label ([#130](https://github.com/Larasell-dev/admin/issues/130)) ([045b94a](https://github.com/Larasell-dev/admin/commit/045b94a0ea72d177ad85734800f9813d5433ed4f))
* control the image lightbox with an open prop and drop stale image links ([6f35a71](https://github.com/Larasell-dev/admin/commit/6f35a7129a2794d3dc96631e29bd3f6437028085))
* freeze the mobile nav while it closes ([6a81c0e](https://github.com/Larasell-dev/admin/commit/6a81c0e9e3feb91c69516a31b23e4ea418955719))
* keep the add images input inside its square ([870e464](https://github.com/Larasell-dev/admin/commit/870e4640094877b7441bb4dafff9b3eaec4dcedc))
* keep the confirmation dialog's content unchanged while it closes ([#123](https://github.com/Larasell-dev/admin/issues/123)) ([0d6d1a0](https://github.com/Larasell-dev/admin/commit/0d6d1a0bf4c4af348873e130e0e08944d3f0c404))
* keep the product form's last field clear of the unsaved changes toast ([8a15c17](https://github.com/Larasell-dev/admin/commit/8a15c17a358badf7deeb0c3eed225c8192cf5ddc))
* leave out the actions menu on rows whose actions are all hidden ([c0a5016](https://github.com/Larasell-dev/admin/commit/c0a5016aea26b4e4ab7513810e40c8896ee07f29))
* let the category delete dialog animate out after deleting ([#122](https://github.com/Larasell-dev/admin/issues/122)) ([4dd69e5](https://github.com/Larasell-dev/admin/commit/4dd69e5abe392b5e429036654dd528c24a7d13e5))
* only let siblings stop a navigation item from matching its pages ([#131](https://github.com/Larasell-dev/admin/issues/131)) ([60898db](https://github.com/Larasell-dev/admin/commit/60898db6ef8aff32a0720f872df6fe63c186bda0))
* put dialogs opened later above earlier ones ([c6c87bc](https://github.com/Larasell-dev/admin/commit/c6c87bce924562d98bd5ad2c741edbe25c189652))
* shorten the stale image link toast so it fits on one line ([520b3a4](https://github.com/Larasell-dev/admin/commit/520b3a4dd5bbc886aa46f443246c0f84e503a30f))
* show a back link instead of the category tree trigger when there are no categories ([#121](https://github.com/Larasell-dev/admin/issues/121)) ([d7fd8b9](https://github.com/Larasell-dev/admin/commit/d7fd8b96a9bef51e9c19d9382b45d2ec5ffcbd12))
* stack the add product search above its buttons on narrow screens ([#120](https://github.com/Larasell-dev/admin/issues/120)) ([891acd0](https://github.com/Larasell-dev/admin/commit/891acd09dd0f80b5030316a6af5800ef800875b8))
* stop avatar uploads on cancel and explain why they fail ([fd66549](https://github.com/Larasell-dev/admin/commit/fd66549a9a8f637db55b8e33e6efa6eff4dfde20))
* stop the page jumping when the rows per page select opens ([675a52a](https://github.com/Larasell-dev/admin/commit/675a52afc407b938e9eac11ee247fcbeb01225b5))
* upload product images one at a time and explain each failure ([13ee622](https://github.com/Larasell-dev/admin/commit/13ee622df48b06f1d63ade289dd8bdbf953caf1c))
* visit the dev login with an Inertia link to avoid a full reload ([24d13ac](https://github.com/Larasell-dev/admin/commit/24d13ac30a945568e9b97cb56539abf9961a5437))


### Miscellaneous Chores

* add a phpstan rule that prefers route() over hard-coded URLs in tests ([41679fb](https://github.com/Larasell-dev/admin/commit/41679fb0e904b009f438f028ecc0f7d43163e5d1))
* add an oxlint rule that asks for cn() in class lists ([258ceee](https://github.com/Larasell-dev/admin/commit/258ceee671c84bf87c815308b33c79cfe480280e))
* add an oxlint rule that forbids positive margins ([#147](https://github.com/Larasell-dev/admin/issues/147)) ([7776bf5](https://github.com/Larasell-dev/admin/commit/7776bf50dcd9a64f765171270be08c7a0daa31c8))
* enable oxlint plugins and type-aware linting ([#148](https://github.com/Larasell-dev/admin/issues/148)) ([95b8672](https://github.com/Larasell-dev/admin/commit/95b86723b8741ae6f523c07d5a2c64edde94a786))
* report oxlint correctness rules as errors ([#149](https://github.com/Larasell-dev/admin/issues/149)) ([27d9ad5](https://github.com/Larasell-dev/admin/commit/27d9ad5eb06d48ddd896e66af34d51f461faa867))
* store product images on the public disk in the workbench ([d6510c4](https://github.com/Larasell-dev/admin/commit/d6510c4f0c99ca7e49e533e85f95360751e985d4))
* update @larasell-dev/ui to 0.1.19 ([#138](https://github.com/Larasell-dev/admin/issues/138)) ([f50b174](https://github.com/Larasell-dev/admin/commit/f50b174ddc1dc27346e963830018324577547c7c))
* update @larasell-dev/ui to 0.1.25 ([f677aef](https://github.com/Larasell-dev/admin/commit/f677aefd3c3fe665db844f3e47ca4f39d5546232))

### Upgrade guide

#### Admin pages import `@larasell-dev/admin` and register with `Larasell.booting()`

The `larasell-admin/register`, `larasell-admin/components` and `larasell-admin/layouts` modules
are now one module, `@larasell-dev/admin`. Pages are registered in a `Larasell.booting()`
callback and load when they're first opened instead of on every admin page. Every export of
React, Inertia and `@larasell-dev/ui` is now available to admin pages, not only the hooks and
components that were listed by hand.

##### Who is affected

Apps with their own admin pages in `resources/js/admin`, built with the `admin()` Vite plugin.
Apps without that directory are not affected.

##### How to upgrade

Register pages with a loader in `resources/js/admin/app.tsx`:

```tsx
// Before
import { page } from "larasell-admin/register";
import Report from "./pages/Report";

page.add("Report", Report);

// After
import { Larasell } from "@larasell-dev/admin";

Larasell.booting(({ pages }) => {
  pages.register("Report", () => import("./pages/Report"));
});
```

Import components and layouts from `@larasell-dev/admin`:

```tsx
// Before
import { ResourceTable } from "larasell-admin/components";
import { SidebarLayout } from "larasell-admin/layouts";

// After
import { ResourceTable, SidebarLayout } from "@larasell-dev/admin";
```

The `TableResource` type and its parts now match what tables send: they include
`headerActions`, `pagination`, badge columns and row links.

#### The config now lives in `config/larasell/admin/panel.php` under `larasell.admin.panel`

The admin config can now be published with `php artisan vendor:publish --tag=larasell-admin-config`.
It is published into its own `config/larasell/admin` folder, so future config files can sit
next to `panel.php` without crowding the app's `config` directory. Laravel names config keys
after their folder, so the key changed from `larasell-admin` to `larasell.admin.panel`.

##### Who is affected

- Apps that read or set `larasell-admin.path`, `larasell-admin.disk`, or
  `larasell-admin.tables.namespace`, for example with `config()`.
- Apps with their own `config/larasell-admin.php`. The panel no longer reads that file, so its
  values are silently ignored.

Apps that only set `LARASELL_ADMIN_PATH` or `LARASELL_ADMIN_DISK` in `.env` are not affected.

##### How to upgrade

Rename the config keys:

```php
// Before
config('larasell-admin.path');
config('larasell-admin.disk');
config(['larasell-admin.tables.namespace' => 'App\\Admin\\Tables']);

// After
config('larasell.admin.panel.path');
config('larasell.admin.panel.disk');
config(['larasell.admin.panel.tables.namespace' => 'App\\Admin\\Tables']);
```

If you have a `config/larasell-admin.php`, publish the new file, copy your values into it, and
delete the old one:

```sh
php artisan vendor:publish --tag=larasell-admin-config
# copy your values from config/larasell-admin.php into config/larasell/admin/panel.php
rm config/larasell-admin.php
```

#### Customers and Settings moved down one navigation position

The new Orders navigation item takes position 4000, between Categories and Customers. To keep the
built-in items 1000 apart, Customers moved from 4000 to 5000 and Settings from 5000 to 6000.

##### Who is affected

Apps that add navigation items with a fixed `position()` above 3000. An item at 4500 used to sit
between Customers and Settings; it now sits between Orders and Customers. Apps that place items
with `before()` or `after()`, or with `NavItem::...->position()`, keep their order and are not
affected.

##### How to upgrade

Add 1000 to positions above 3000 to keep an item where it was. Better, place it relative to a
built-in item so it follows any future change:

```php
use Larasell\Admin\Enums\NavItem;
use Larasell\Admin\Facades\Navigation;

// Before: between Customers and Settings
Navigation::add('Reports', 'reports.index', 'bar-chart')->position(4500);

// After
Navigation::add('Reports', 'reports.index', 'bar-chart')->position(5500);

// Or
Navigation::add('Reports', 'reports.index', 'bar-chart')->after(NavItem::Customers);
```

#### Customers and Settings moved down one navigation position

The new Products navigation item takes position 3000, between Categories and Customers. To keep
the built-in items 1000 apart, Customers moved from 3000 to 4000 and Settings from 4000 to 5000.

##### Who is affected

Apps that add navigation items with a fixed `position()` above 2000. An item at 3500 used to sit
between Customers and Settings; it now sits between Products and Customers. Apps that place items
with `before()` or `after()`, or with `NavItem::...->position()`, keep their order and are not
affected.

##### How to upgrade

Add 1000 to positions above 2000 to keep an item where it was. Better, place it relative to a
built-in item so it follows any future change:

```php
use Larasell\Admin\Enums\NavItem;
use Larasell\Admin\Facades\Navigation;

// Before: between Customers and Settings
Navigation::add('Reports', 'reports.index', 'bar-chart')->position(3500);

// After
Navigation::add('Reports', 'reports.index', 'bar-chart')->position(4500);

// Or
Navigation::add('Reports', 'reports.index', 'bar-chart')->after(NavItem::Customers);
```

## [0.4.1](https://github.com/Larasell-dev/admin/compare/v0.4.0...v0.4.1) (2026-10-23)


### Bug Fixes

* boot Laravel Head before the admin routes use withHead() ([#114](https://github.com/Larasell-dev/admin/issues/114)) ([f7be632](https://github.com/Larasell-dev/admin/commit/f7be632a7e734340909a41236a3c3031bb0069be))

## [0.4.0](https://github.com/Larasell-dev/admin/compare/v0.3.2...v0.4.0) (2026-10-23)


### ⚠ BREAKING CHANGES

* infer the model a table lists from its class name ([#101](https://github.com/Larasell-dev/admin/issues/101))
* give every admin page its own title ([#99](https://github.com/Larasell-dev/admin/issues/99))

### Features

* add a category tree ([#89](https://github.com/Larasell-dev/admin/issues/89)) ([708fe19](https://github.com/Larasell-dev/admin/commit/708fe193f0f440e6df3e3b273b20970234ea6b24))
* add a headless tree component ([#88](https://github.com/Larasell-dev/admin/issues/88)) ([b4e13f5](https://github.com/Larasell-dev/admin/commit/b4e13f5d537d2fbc5e956a73b04b919459ff62a1))
* add a log in as admin button to the login page in local ([dc4732e](https://github.com/Larasell-dev/admin/commit/dc4732eb8b26f3d06d3bc571c42ae5dfedf16419))
* add a subcategory from the category tree ([cea1408](https://github.com/Larasell-dev/admin/commit/cea140834496e22b5a3796612b8a5c19fcb81a1c))
* add an unsaved changes toast for Inertia forms ([835b0e4](https://github.com/Larasell-dev/admin/commit/835b0e4fc3a5d05c0000616aa99dbeb340b5648f))
* add category pages ([#90](https://github.com/Larasell-dev/admin/issues/90)) ([8c97b5e](https://github.com/Larasell-dev/admin/commit/8c97b5e1ae99c14c105064c3a9f8b6b643f6cfa7))
* add Section, SectionStack and Inset layout components ([9745bbf](https://github.com/Larasell-dev/admin/commit/9745bbf9ad31dd11c94091abc81307180689a984))
* always show the category menu button ([#105](https://github.com/Larasell-dev/admin/issues/105)) ([514f7bf](https://github.com/Larasell-dev/admin/commit/514f7bfff5c809b532044ad0c525781d69f20199))
* create and edit categories ([5d6c67c](https://github.com/Larasell-dev/admin/commit/5d6c67c2c21ad4f3493f5c48d223c2ed886bb719))
* delete a category with all categories below it ([b8f5607](https://github.com/Larasell-dev/admin/commit/b8f56078de9153426ef7ca7ff59dd8dfec0f59c8))
* give every admin page its own title ([#99](https://github.com/Larasell-dev/admin/issues/99)) ([6de6534](https://github.com/Larasell-dev/admin/commit/6de6534d2a971f3019462ef2924ee10212ce68f7))
* give the panel's own tables larasell: slugs ([07e59a7](https://github.com/Larasell-dev/admin/commit/07e59a7d409257b246c9c6419b6d1180ff82d9ad))
* infer the model a table lists from its class name ([#101](https://github.com/Larasell-dev/admin/issues/101)) ([9e3bbd6](https://github.com/Larasell-dev/admin/commit/9e3bbd69c498c112e18096865985ff119ca461d8))
* let tables take constructor arguments for their actions ([#93](https://github.com/Larasell-dev/admin/issues/93)) ([aec3460](https://github.com/Larasell-dev/admin/commit/aec3460fb75427ec0211c2e77fc6def561a94dbc))
* list, add and remove a category's products ([d913115](https://github.com/Larasell-dev/admin/commit/d913115a91441d4fed122f44e22e8ef4e91b45c1))
* move categories by dragging them in the tree ([436323f](https://github.com/Larasell-dev/admin/commit/436323f96b01d29975a4bd3338fe96bb9e36ad88))
* offer to add the first category when there are none ([50c6876](https://github.com/Larasell-dev/admin/commit/50c687643a9d76485e636328e69a52648b5b428f))
* open categories with a chevron and show their number of children ([#106](https://github.com/Larasell-dev/admin/issues/106)) ([7588d1a](https://github.com/Larasell-dev/admin/commit/7588d1ad8d52b62d393b004d02291ee30878788b))
* open the category tree in a dialog on mobile ([#98](https://github.com/Larasell-dev/admin/issues/98)) ([3ef1c06](https://github.com/Larasell-dev/admin/commit/3ef1c0650518bb8c4a343477c08f046e27253133))
* open the current category instead of the clicked one ([#107](https://github.com/Larasell-dev/admin/issues/107)) ([46c791e](https://github.com/Larasell-dev/admin/commit/46c791e727bb9c575a28983049d0685e21d98d4c))
* position built-in nav items via the NavItem enum ([#103](https://github.com/Larasell-dev/admin/issues/103)) ([40094b6](https://github.com/Larasell-dev/admin/commit/40094b6b9123c0b7ceae251ca0efad44326f1193))
* show an empty state for a category that no longer exists ([#102](https://github.com/Larasell-dev/admin/issues/102)) ([c8c4610](https://github.com/Larasell-dev/admin/commit/c8c461048a11db3d1b917e5173a65923735d7271))


### Bug Fixes

* align the mobile header with the sidebar content ([15b9cc7](https://github.com/Larasell-dev/admin/commit/15b9cc7cdd6cc440339da5c7f7c449dba747a7d0))
* clear the category tree's scroll fade once it no longer scrolls ([#111](https://github.com/Larasell-dev/admin/issues/111)) ([bf517e6](https://github.com/Larasell-dev/admin/commit/bf517e695a60efabb660dcbbff5f4ff074645229))
* flush every prefetched page after a change ([67eb6b9](https://github.com/Larasell-dev/admin/commit/67eb6b9735d5dc5950942f85a968e3d87a366c37))
* keep deeply nested category names readable in the tree ([#110](https://github.com/Larasell-dev/admin/issues/110)) ([b84b397](https://github.com/Larasell-dev/admin/commit/b84b39797d3f5cccd3d2aae15d7335a37ceca7c1))
* keep the mobile category tree unchanged while its dialog closes ([#108](https://github.com/Larasell-dev/admin/issues/108)) ([9157791](https://github.com/Larasell-dev/admin/commit/9157791b8ee251d9f59d9f455f889e037b9596b3))
* open the path to the current category on every page load ([a652495](https://github.com/Larasell-dev/admin/commit/a652495dbb5a6adf61b9778120ad22ac9f4d0982))
* remember the mobile category tree's open categories ([#109](https://github.com/Larasell-dev/admin/issues/109)) ([da08fd1](https://github.com/Larasell-dev/admin/commit/da08fd18f4fe79868192643721cf1a105f9d09a6))


### Miscellaneous Chores

* fix the Amp orb setup when it runs without root ([9dcd95e](https://github.com/Larasell-dev/admin/commit/9dcd95e4618caa6f3e9cd6daa8f0390736657273))

### Upgrade guide

#### Admin page titles now come from Laravel Head

Every admin page now has its own title, such as "Customers - Admin", set on the
server with [Laravel Head](https://laravel.com/framework/docs/head) instead of
Inertia's `<Head>` component. The admin panel no longer adds " - Admin" to
titles set with `<Head title="...">`.

##### Who is affected

Apps with their own admin pages, registered in `resources/js/admin/pages`, that
set a title with Inertia's `<Head>` component. Such a title now shows without
the " - Admin" suffix and competes with the title Laravel Head sets. Apps that
only use `Route::index()` table pages are not affected: those pages are titled
after their table.

##### How to upgrade

Remove the `<Head>` component from your page and set the title on its route in
`routes/admin.php` instead. The admin panel adds the " - Admin" suffix.

```tsx
// Before: resources/js/admin/pages/Reports.tsx
<Head title="Reports" />
```

```php
// After: routes/admin.php
Route::get('reports', ReportController::class)
    ->name('reports')
    ->withHead(title: 'Reports');
```

For a title that depends on the request, call `Head::title()` in the controller:

```php
use Laravel\Head\Facades\Head;

Head::title($report->name);
```

#### Default table titles no longer end in "table"

A table's default title now leaves out a `Table` suffix in its class name, so
`ProductReviewsTable` is titled "Product reviews" instead of "Product reviews
table". Tables can now be named with the suffix they infer their model from,
like `ProductReviewTable`, without it showing up in the title.

##### Who is affected

Tables whose class name ends in `Table` and that don't override `title()`. The
new title also shows up in the page title of their `Route::index` route. Tables
without the suffix, and tables that define `title()`, are not affected.

##### How to upgrade

To keep the old title, return it from `title()`:

```php
use Larasell\Admin\Tables\Table;

class ProductReviewsTable extends Table
{
    // Before: no title() method, titled "Product reviews table"

    // After
    public function title(): string
    {
        return 'Product reviews table';
    }
}
```

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
