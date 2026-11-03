# The admin panel uses Chronicle 0.4

The admin panel's structured exceptions now implement Chronicle 0.4's
`Larasell\Chronicle\Exceptions\StructuredException` instead of `Larasell\Chronicle\StructuredError`.
This covers `AuditFailed`, `AdminRemovalFailed`, `InvalidNavigationItem`, `InvalidTableAction`,
`InvalidTableColumn` and `InvalidTableEmptyState`. Chronicle 0.4 bounds and redacts every event it
logs and adds a fake for tests. The admin panel also requires Larasell 1.23, the first version on
Chronicle 0.4.

## Who is affected

- Apps that catch the admin panel's exceptions as `Larasell\Chronicle\StructuredError`.
- Apps that use Chronicle themselves, e.g. its `LogRequests` middleware or structured exceptions.
  Composer now installs Chronicle 0.4, so follow
  [Chronicle's upgrade guide](https://github.com/Larasell-dev/chronicle/blob/main/CHANGELOG.md).

Apps that catch these exceptions by name, or only read `errorCode()`, `errorWhy()`, `errorFix()`
and `errorLink()`, are not affected.

## How to upgrade

Update Larasell and the admin panel together:

```bash
composer update larasell-dev/larasell larasell-dev/admin --with-dependencies
```

Catch the new interface:

```php
// Before
use Larasell\Chronicle\StructuredError;

try {
    // Code that builds an admin table, navigation or audit record
} catch (StructuredError $exception) {
    report($exception);
}

// After
use Larasell\Chronicle\Exceptions\StructuredException;

try {
    // Code that builds an admin table, navigation or audit record
} catch (StructuredException $exception) {
    report($exception);
}
```
