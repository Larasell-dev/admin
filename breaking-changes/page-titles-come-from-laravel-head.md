# Admin page titles now come from Laravel Head

Every admin page now has its own title, such as "Customers - Admin", set on the
server with [Laravel Head](https://laravel.com/framework/docs/head) instead of
Inertia's `<Head>` component. The admin panel no longer adds " - Admin" to
titles set with `<Head title="...">`.

## Who is affected

Apps with their own admin pages, registered in `resources/js/admin/pages`, that
set a title with Inertia's `<Head>` component. Such a title now shows without
the " - Admin" suffix and competes with the title Laravel Head sets. Apps that
only use `Route::index()` table pages are not affected: those pages are titled
after their table.

## How to upgrade

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
