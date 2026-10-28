# Customers and Settings moved down one navigation position

The new Orders navigation item takes position 4000, between Categories and Customers. To keep the
built-in items 1000 apart, Customers moved from 4000 to 5000 and Settings from 5000 to 6000.

## Who is affected

Apps that add navigation items with a fixed `position()` above 3000. An item at 4500 used to sit
between Customers and Settings; it now sits between Orders and Customers. Apps that place items
with `before()` or `after()`, or with `NavItem::...->position()`, keep their order and are not
affected.

## How to upgrade

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
