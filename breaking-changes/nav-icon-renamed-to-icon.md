# The `NavIcon` enum is now `Icon`

`Larasell\Admin\Enums\NavIcon` is renamed to `Larasell\Admin\Enums\Icon`. The
enum is no longer only for navigation items: tables use it for their empty
state too, so the old name was misleading. Its cases and values are unchanged.

## Who is affected

Apps that import `Larasell\Admin\Enums\NavIcon`, for example to register
navigation items or to set a table's empty state icon. Apps that pass icons as
strings such as `'home'` are not affected.

## How to upgrade

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
