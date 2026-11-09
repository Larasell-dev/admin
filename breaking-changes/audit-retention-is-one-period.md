# Audit records are kept for one retention period, and only the oldest are pruned

Audit records are now chained to each other, so changes made outside the app show when the log
is verified with `php artisan larasell:verify-audit-log`. A chain can only lose records from its
start, so the per-category retention periods are gone. `larasell:prune-audit-log` now deletes
the oldest records up to the first one still in the period.

## Who is affected

Apps that published `config/larasell/admin/audit.php` and set `retention.default_days` or
`retention.categories`. Apps that only set `LARASELL_ADMIN_AUDIT_RETENTION_DAYS` are not affected.

Code that reads `audit_log.pruned` records: they are now version 2, with `retention_days` and
`deleted` as single numbers and a new `starts_after`.

## How to upgrade

Run `php artisan migrate`. It chains the records stored so far.

Then keep one period in the published config:

```php
// Before
'retention' => [
    'default_days' => env('LARASELL_ADMIN_AUDIT_RETENTION_DAYS'),
    'categories' => [
        'security' => 730,
    ],
],

// After
'retention' => [
    'days' => env('LARASELL_ADMIN_AUDIT_RETENTION_DAYS'),
],
```

Use the longest period you need to keep any of the records for.
