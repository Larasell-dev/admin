# The config now lives in `config/larasell/admin/panel.php` under `larasell.admin.panel`

The admin config can now be published with `php artisan vendor:publish --tag=larasell-admin-config`.
It is published into its own `config/larasell/admin` folder, so future config files can sit
next to `panel.php` without crowding the app's `config` directory. Laravel names config keys
after their folder, so the key changed from `larasell-admin` to `larasell.admin.panel`.

## Who is affected

- Apps that read or set `larasell-admin.path`, `larasell-admin.disk`, or
  `larasell-admin.tables.namespace`, for example with `config()`.
- Apps with their own `config/larasell-admin.php`. The panel no longer reads that file, so its
  values are silently ignored.

Apps that only set `LARASELL_ADMIN_PATH` or `LARASELL_ADMIN_DISK` in `.env` are not affected.

## How to upgrade

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
