# The code is grouped by module

The panel's classes used to be filed by type, e.g. `Http\Controllers`, `Data\View` and `Tables`, so
one feature was spread over up to ten folders. They are now grouped by feature: each module is a
namespace of its own, such as `Larasell\Admin\Product` or `Larasell\Admin\Category`, and what
modules and apps plug into lives under `Larasell\Admin\Platform`. Every class keeps its name, apart
from the ones the Team rename changed, but most moved to another namespace. The old namespaces don't
work anymore; there are no aliases.

## Who is affected

Apps that import any of the panel's classes. The common ones are the table API (`Table`, its
columns, actions and attributes), `Icon`, `NavItem` and the `Navigation` facade, the `Admin` model,
the audit API (`Auditor`, `AuditRecord`, `AuditCategory`, `Subject` and the actors) and the content
locale contracts.

Apps that only configure the panel, add pages through `routes/admin.php` and
`resources/js/admin`, and import nothing from `Larasell\Admin` are not affected. `AdminPanel` and
`AdminServiceProvider` didn't move.

## How to upgrade

Change the imports. The ones most apps use:

```php
// Before
use Larasell\Admin\Enums\Icon;
use Larasell\Admin\Enums\NavItem;
use Larasell\Admin\Enums\Table\Variant;
use Larasell\Admin\Facades\Navigation;
use Larasell\Admin\Models\Admin;
use Larasell\Admin\Audit\AuditRecord;
use Larasell\Admin\Tables\Actions\Action;
use Larasell\Admin\Tables\Columns\TextColumn;
use Larasell\Admin\Tables\Table;

// After
use Larasell\Admin\Platform\Panel\Icon;
use Larasell\Admin\Platform\Navigation\NavItem;
use Larasell\Admin\Platform\Tables\Enums\Variant;
use Larasell\Admin\Platform\Navigation\Facades\Navigation;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Admin\Platform\Audit\AuditRecord;
use Larasell\Admin\Platform\Tables\Actions\Action;
use Larasell\Admin\Platform\Tables\Columns\TextColumn;
use Larasell\Admin\Platform\Tables\Table;
```

Classes that `php artisan make:larasell-table` and `make:larasell-audit-record` generate from now
on already import the new namespaces.

Every moved class, without the `Larasell\Admin\` prefix:

<details>
<summary>All moved classes</summary>

| Before                                                              | After                                                                   |
| ------------------------------------------------------------------- | ----------------------------------------------------------------------- |
| `ServiceProviders\ApiTokenServiceProvider`                          | `ApiToken\ApiTokenServiceProvider`                                      |
| `Models\ApiToken`                                                   | `ApiToken\Models\ApiToken`                                              |
| `Actions\Categories\DeleteCategory`                                 | `Category\Actions\DeleteCategory`                                       |
| `Actions\Categories\MoveCategory`                                   | `Category\Actions\MoveCategory`                                         |
| `Payloads\Categories\MoveCategoryPayload`                           | `Category\Actions\MoveCategoryPayload`                                  |
| `ServiceProviders\CategoryServiceProvider`                          | `Category\CategoryServiceProvider`                                      |
| `Data\View\Categories\CategoriesPageData`                           | `Category\Data\CategoriesPageData`                                      |
| `Data\ViewModel\Categories\CategoryData`                            | `Category\Data\CategoryData`                                            |
| `Data\ViewModel\Categories\CategoryDetailData`                      | `Category\Data\CategoryDetailData`                                      |
| `Data\ViewModel\Categories\CategoryOptionData`                      | `Category\Data\CategoryOptionData`                                      |
| `Data\View\Categories\CategoryPageData`                             | `Category\Data\CategoryPageData`                                        |
| `Data\View\Categories\CreateCategoryPageData`                       | `Category\Data\CreateCategoryPageData`                                  |
| `Data\View\Categories\MissingCategoryPageData`                      | `Category\Data\MissingCategoryPageData`                                 |
| `Http\Controllers\Categories\CategoryController`                    | `Category\Http\Controllers\CategoryController`                          |
| `Http\Controllers\Categories\CategoryProductController`             | `Category\Http\Controllers\CategoryProductController`                   |
| `Http\Controllers\Categories\HideCategoryController`                | `Category\Http\Controllers\HideCategoryController`                      |
| `Http\Controllers\Categories\MoveCategoryController`                | `Category\Http\Controllers\MoveCategoryController`                      |
| `Http\Controllers\Categories\SearchCategoriesController`            | `Category\Http\Controllers\SearchCategoriesController`                  |
| `Http\Controllers\Categories\SearchProductsNotInCategoryController` | `Category\Http\Controllers\SearchProductsNotInCategoryController`       |
| `Http\Controllers\Categories\UnhideCategoryController`              | `Category\Http\Controllers\UnhideCategoryController`                    |
| `Http\Requests\Categories\AddProductToCategoryRequest`              | `Category\Http\Requests\AddProductToCategoryRequest`                    |
| `Http\Requests\Categories\CategoryRequest`                          | `Category\Http\Requests\CategoryRequest`                                |
| `Http\Requests\Categories\MoveCategoryRequest`                      | `Category\Http\Requests\MoveCategoryRequest`                            |
| `Tables\Categories\CategoryProducts`                                | `Category\Tables\CategoryProducts`                                      |
| `Actions\Customers\DeleteCustomer`                                  | `Customer\Actions\DeleteCustomer`                                       |
| `ServiceProviders\CustomerServiceProvider`                          | `Customer\CustomerServiceProvider`                                      |
| `Data\View\Customers\CreateCustomerPageData`                        | `Customer\Data\CreateCustomerPageData`                                  |
| `Data\ViewModel\Customers\CustomerDetailData`                       | `Customer\Data\CustomerDetailData`                                      |
| `Data\View\Customers\EditCustomerPageData`                          | `Customer\Data\EditCustomerPageData`                                    |
| `Http\Controllers\Customers\CustomerController`                     | `Customer\Http\Controllers\CustomerController`                          |
| `Http\Controllers\Customers\SearchCustomersController`              | `Customer\Http\Controllers\SearchCustomersController`                   |
| `Http\Requests\Customers\CustomerRequest`                           | `Customer\Http\Requests\CustomerRequest`                                |
| `Tables\Customers\Customers`                                        | `Customer\Tables\Customers`                                             |
| `Data\ViewModel\Orders\AddressData`                                 | `Order\Data\AddressData`                                                |
| `Data\ViewModel\Orders\AmountData`                                  | `Order\Data\AmountData`                                                 |
| `Data\ViewModel\Orders\BadgeData`                                   | `Order\Data\BadgeData`                                                  |
| `Data\ViewModel\Orders\OrderDetailData`                             | `Order\Data\OrderDetailData`                                            |
| `Data\ViewModel\Orders\OrderItemData`                               | `Order\Data\OrderItemData`                                              |
| `Data\View\Orders\OrderPageData`                                    | `Order\Data\OrderPageData`                                              |
| `Data\ViewModel\Orders\PaymentData`                                 | `Order\Data\PaymentData`                                                |
| `Http\Controllers\Orders\OrderController`                           | `Order\Http\Controllers\OrderController`                                |
| `Http\Controllers\Orders\SearchOrdersController`                    | `Order\Http\Controllers\SearchOrdersController`                         |
| `ServiceProviders\OrderServiceProvider`                             | `Order\OrderServiceProvider`                                            |
| `Tables\Customers\CustomerOrders`                                   | `Order\Tables\CustomerOrders`                                           |
| `Tables\Orders\Orders`                                              | `Order\Tables\Orders`                                                   |
| `Audit\Actor`                                                       | `Platform\Audit\Actors\Actor`                                           |
| `Audit\AuditCategory`                                               | `Platform\Audit\Enums\AuditCategory`                                    |
| `Exceptions\AuditFailed`                                            | `Platform\Audit\Exceptions\AuditFailed`                                 |
| `Audit\AuditRecord`                                                 | `Platform\Audit\AuditRecord`                                            |
| `Audit\Auditor`                                                     | `Platform\Audit\Auditor`                                                |
| `Console\PruneAuditLogCommand`                                      | `Platform\Audit\Log\Console\PruneAuditLogCommand`                       |
| `Audit\Subject`                                                     | `Platform\Audit\Subject`                                                |
| `Audit\SystemActor`                                                 | `Platform\Audit\Actors\SystemActor`                                     |
| `Audit\UnknownActor`                                                | `Platform\Audit\Actors\UnknownActor`                                    |
| `Data\View\LoginPageData`                                           | `Platform\Auth\Data\LoginPageData`                                      |
| `Http\Controllers\LoginController`                                  | `Platform\Auth\Http\Controllers\LoginController`                        |
| `Http\Controllers\LogoutController`                                 | `Platform\Auth\Http\Controllers\LogoutController`                       |
| `Http\Middleware\Authenticate`                                      | `Platform\Auth\Http\Middleware\Authenticate`                            |
| `Http\Middleware\RedirectIfAuthenticated`                           | `Platform\Auth\Http\Middleware\RedirectIfAuthenticated`                 |
| `Http\Requests\LoginRequest`                                        | `Platform\Auth\Http\Requests\LoginRequest`                              |
| `Models\Admin`                                                      | `Platform\Auth\Models\Admin`                                            |
| `Actions\ContentLocale\SetContentLocale`                            | `Platform\ContentLocale\Actions\SetContentLocale`                       |
| `Payloads\ContentLocale\SetContentLocalePayload`                    | `Platform\ContentLocale\Actions\SetContentLocalePayload`                |
| `ContentLocale\AvailableLocales`                                    | `Platform\ContentLocale\Locales\AvailableLocales`                       |
| `ContentLocale\ContentLocaleResolver`                               | `Platform\ContentLocale\Resolvers\ContentLocaleResolver`                |
| `ContentLocale\CurrentContentLocale`                                | `Platform\ContentLocale\CurrentContentLocale`                           |
| `Data\ViewModel\ContentLocale\ContentLocaleData`                    | `Platform\ContentLocale\Data\ContentLocaleData`                         |
| `Data\ViewModel\ContentLocale\ContentLocaleOptionData`              | `Platform\ContentLocale\Data\ContentLocaleOptionData`                   |
| `Http\Controllers\ContentLocale\SwitchContentLocaleController`      | `Platform\ContentLocale\Http\Controllers\SwitchContentLocaleController` |
| `Http\Requests\ContentLocale\SetContentLocaleRequest`               | `Platform\ContentLocale\Http\Requests\SetContentLocaleRequest`          |
| `ContentLocale\SessionContentLocaleResolver`                        | `Platform\ContentLocale\Resolvers\SessionContentLocaleResolver`         |
| `ContentLocale\StoreLocales`                                        | `Platform\ContentLocale\Locales\StoreLocales`                           |
| `Exceptions\InvalidNavigationItem`                                  | `Platform\Navigation\Exceptions\InvalidNavigationItem`                  |
| `Enums\NavItem`                                                     | `Platform\Navigation\NavItem`                                           |
| `Navigation`                                                        | `Platform\Navigation\Navigation`                                        |
| `NavigationGroup`                                                   | `Platform\Navigation\NavigationGroup`                                   |
| `ValueObjects\NavigationItem`                                       | `Platform\Navigation\NavigationItem`                                    |
| `Data\ViewModel\NavigationItemData`                                 | `Platform\Navigation\Data\NavigationItemData`                           |
| `Console\LinkAdminAssetsCommand`                                    | `Platform\Panel\Console\LinkAdminAssetsCommand`                         |
| `Data\ViewModel\CommandMenuData`                                    | `Platform\Panel\Data\CommandMenuData`                                   |
| `Data\View\HomePageData`                                            | `Platform\Panel\Data\HomePageData`                                      |
| `Data\ViewModel\PaginatedData`                                      | `Platform\Panel\Data\PaginatedData`                                     |
| `Http\Controllers\Dev\RouteController`                              | `Platform\Panel\Http\Controllers\Dev\RouteController`                   |
| `Http\Controllers\Dev\SessionController`                            | `Platform\Panel\Http\Controllers\Dev\SessionController`                 |
| `Http\Controllers\HomeController`                                   | `Platform\Panel\Http\Controllers\HomeController`                        |
| `Http\Controllers\SettingsController`                               | `Platform\Panel\Http\Controllers\SettingsController`                    |
| `Http\Middleware\HandleAdminInertiaRequests`                        | `Platform\Panel\Http\Middleware\HandleAdminInertiaRequests`             |
| `Http\Middleware\LogLicensingPlan`                                  | `Platform\Panel\Http\Middleware\LogLicensingPlan`                       |
| `Enums\Icon`                                                        | `Platform\Panel\Icon`                                                   |
| `Data\ViewModel\Search\SearchData`                                  | `Platform\Search\Data\SearchData`                                       |
| `Data\ViewModel\Search\SearchMatchData`                             | `Platform\Search\Data\SearchMatchData`                                  |
| `Data\ViewModel\Search\SearchResultData`                            | `Platform\Search\Data\SearchResultData`                                 |
| `Http\Requests\SearchRequest`                                       | `Platform\Search\Http\Requests\SearchRequest`                           |
| `Search\SearchQuery`                                                | `Platform\Search\SearchQuery`                                           |
| `Search\Searches`                                                   | `Platform\Search\Searches`                                              |
| `Search\TextMatch`                                                  | `Platform\Search\TextMatch`                                             |
| `Enums\Concerns\HasLabel`                                           | `Platform\Support\Concerns\HasLabel`                                    |
| `Rules\Slug`                                                        | `Platform\Support\Rules\Slug`                                           |
| `Tables\Actions\Action`                                             | `Platform\Tables\Actions\Action`                                        |
| `Tables\Actions\TableAction`                                        | `Platform\Tables\Actions\TableAction`                                   |
| `Tables\Attributes\Remember`                                        | `Platform\Tables\Attributes\Remember`                                   |
| `Tables\Attributes\UseModel`                                        | `Platform\Tables\Attributes\UseModel`                                   |
| `Tables\Columns\BadgeColumn`                                        | `Platform\Tables\Columns\BadgeColumn`                                   |
| `Tables\Columns\CellImage`                                          | `Platform\Tables\Columns\CellImage`                                     |
| `Tables\Columns\Column`                                             | `Platform\Tables\Columns\Column`                                        |
| `Tables\Columns\Concerns\HasTextValue`                              | `Platform\Tables\Columns\Concerns\HasTextValue`                         |
| `Tables\Columns\IconImage`                                          | `Platform\Tables\Columns\IconImage`                                     |
| `Tables\Columns\Image`                                              | `Platform\Tables\Columns\Image`                                         |
| `Tables\Columns\PictureImage`                                       | `Platform\Tables\Columns\PictureImage`                                  |
| `Tables\Columns\TextColumn`                                         | `Platform\Tables\Columns\TextColumn`                                    |
| `Console\MakeTableCommand`                                          | `Platform\Tables\Console\MakeTableCommand`                              |
| `Enums\Table\ImageStyle`                                            | `Platform\Tables\Enums\ImageStyle`                                      |
| `Enums\Table\Variant`                                               | `Platform\Tables\Enums\Variant`                                         |
| `Exceptions\InvalidTableAction`                                     | `Platform\Tables\Exceptions\InvalidTableAction`                         |
| `Exceptions\InvalidTableColumn`                                     | `Platform\Tables\Exceptions\InvalidTableColumn`                         |
| `Exceptions\InvalidTableEmptyState`                                 | `Platform\Tables\Exceptions\InvalidTableEmptyState`                     |
| `Http\Controllers\TableActionController`                            | `Platform\Tables\Http\Controllers\TableActionController`                |
| `Http\Controllers\TableController`                                  | `Platform\Tables\Http\Controllers\TableController`                      |
| `Tables\Table`                                                      | `Platform\Tables\Table`                                                 |
| `Actions\Product\DeleteProduct`                                     | `Product\Actions\DeleteProduct`                                         |
| `Data\View\CreateProductPageData`                                   | `Product\Data\CreateProductPageData`                                    |
| `Data\ViewModel\DimensionsData`                                     | `Product\Data\DimensionsData`                                           |
| `Data\View\EditProductPageData`                                     | `Product\Data\EditProductPageData`                                      |
| `Data\ViewModel\ProductDetailData`                                  | `Product\Data\ProductDetailData`                                        |
| `Data\ViewModel\ProductImageData`                                   | `Product\Data\ProductImageData`                                         |
| `Data\ViewModel\WeightData`                                         | `Product\Data\WeightData`                                               |
| `Http\Controllers\ProductController`                                | `Product\Http\Controllers\ProductController`                            |
| `Http\Controllers\ProductImageController`                           | `Product\Http\Controllers\ProductImageController`                       |
| `Http\Controllers\SearchProductsController`                         | `Product\Http\Controllers\SearchProductsController`                     |
| `Http\Requests\ProductImagesRequest`                                | `Product\Http\Requests\ProductImagesRequest`                            |
| `Http\Requests\ProductRequest`                                      | `Product\Http\Requests\ProductRequest`                                  |
| `ServiceProviders\ProductServiceProvider`                           | `Product\ProductServiceProvider`                                        |
| `Rules\Barcode`                                                     | `Product\Rules\Barcode`                                                 |
| `Tables\Product\Products`                                           | `Product\Tables\Products`                                               |
| `Actions\CreateAdmin`                                               | `Team\Actions\CreateAdmin`                                              |
| `Actions\DeleteAdmin`                                               | `Team\Actions\DeleteAdmin`                                              |
| `Console\CreateAdminUserCommand`                                    | `Team\Console\CreateAdminUserCommand`                                   |
| `Console\ListAdminsCommand`                                         | `Team\Console\ListAdminsCommand`                                        |
| `Console\RemoveAdminCommand`                                        | `Team\Console\RemoveAdminCommand`                                       |
| `Data\ViewModel\AdminData`                                          | `Team\Data\AdminData`                                                   |
| `Data\View\AdminsPageData`                                          | `Team\Data\TeamPageData`                                                |
| `Data\View\CreateAdminPageData`                                     | `Team\Data\CreateMemberPageData`                                        |
| `Data\View\EditAdminPageData`                                       | `Team\Data\EditMemberPageData`                                          |
| `Data\View\ProfilePageData`                                         | `Team\Data\ProfilePageData`                                             |
| `Exceptions\AdminRemovalFailed`                                     | `Team\Exceptions\AdminRemovalFailed`                                    |
| `Http\Controllers\AdminController`                                  | `Team\Http\Controllers\TeamController`                                  |
| `Http\Controllers\AvatarController`                                 | `Team\Http\Controllers\AvatarController`                                |
| `Http\Controllers\ProfileController`                                | `Team\Http\Controllers\ProfileController`                               |
| `Http\Requests\AdminRequest`                                        | `Team\Http\Requests\MemberRequest`                                      |
| `Http\Requests\AvatarRequest`                                       | `Team\Http\Requests\AvatarRequest`                                      |
| `Http\Requests\ProfileRequest`                                      | `Team\Http\Requests\ProfileRequest`                                     |
| `Rules\AdminPassword`                                               | `Team\Rules\AdminPassword`                                              |
| `Tables\Panel\Admins`                                               | `Team\Tables\Members`                                                   |

</details>
