# The panel's page names are now the paths of their files

The panel's frontend is now grouped by module like its PHP code: a module's pages live in
`resources/js/<Module>/Pages`, the platform's in `resources/js/Platform/<Area>/Pages`. Each page's
Inertia name is its file's path, so `Product/Edit` is now `Product/Pages/Edit`.

## Who is affected

Apps that render one of the panel's own pages with `Inertia::render()`, or that register an
extension page under one of their names. Apps whose pages are their own, such as
`acme-wishlists::Wishlist/Show`, are not affected.

## How to upgrade

Use the new names:

```php
// Before
Inertia::render('TablePage', $props);
Inertia::render('Product/Edit', $props);

// After
Inertia::render('Platform/Tables/Pages/TablePage', $props);
Inertia::render('Product/Pages/Edit', $props);
```

<details>
<summary>All renamed pages</summary>

| Before                | After                                |
| --------------------- | ------------------------------------ |
| `ApiToken/Create`     | `ApiToken/Pages/Create`              |
| `ApiToken/Index`      | `ApiToken/Pages/Index`               |
| `AuditLog/Index`      | `Platform/Audit/Pages/Index`         |
| `Category/Create`     | `Category/Pages/Create`              |
| `Category/Index`      | `Category/Pages/Index`               |
| `Category/Show`       | `Category/Pages/Show`                |
| `Customer/Create`     | `Customer/Pages/Create`              |
| `Customer/Edit`       | `Customer/Pages/Edit`                |
| `GuestNotFound`       | `Platform/Panel/Pages/GuestNotFound` |
| `Home`                | `Platform/Panel/Pages/Home`          |
| `Login`               | `Platform/Auth/Pages/Login`          |
| `NotFound`            | `Platform/Panel/Pages/NotFound`      |
| `Order/Show`          | `Order/Pages/Show`                   |
| `Product/Create`      | `Product/Pages/Create`               |
| `Product/Edit`        | `Product/Pages/Edit`                 |
| `Profile`             | `Team/Pages/Profile`                 |
| `SalesChannel/Create` | `SalesChannel/Pages/Create`          |
| `SalesChannel/Edit`   | `SalesChannel/Pages/Edit`            |
| `TablePage`           | `Platform/Tables/Pages/TablePage`    |
| `Team/Create`         | `Team/Pages/Create`                  |
| `Team/Edit`           | `Team/Pages/Edit`                    |
| `Team/Index`          | `Team/Pages/Index`                   |

</details>
