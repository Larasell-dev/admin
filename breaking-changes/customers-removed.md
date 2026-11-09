# The panel no longer has customers

The Customer module is removed so a new one can be built from scratch.

## Who is affected

Apps or plugins that:

- link to the `larasell.admin.customers.*` or `larasell.admin.search.customers` routes
- use `NavItem::Customers`, or the `larasell-customers:` table slugs
- use any class under `Larasell\Admin\Customer\` or `Larasell\Admin\Order\Tables\CustomerOrders`
- read the `customerUrl` prop of the order page
- publish `config/larasell/admin/customers.php`

Built-in navigation positions changed too: `NavItem::Settings` moved from `6000` to `5000`.

## How to upgrade

Remove what uses these. Delete your published config:

```sh
rm config/larasell/admin/customers.php
```

Navigation items that you placed after Orders and before Settings with a position above
`5000` now sit after Settings. Move them to a position between `4000` and `5000`:

```php
// Before
NavigationFacade::add('Notes', 'notes.index', 'sticky-note')->position(5500);

// After
NavigationFacade::add('Notes', 'notes.index', 'sticky-note')->position(4500);
```
