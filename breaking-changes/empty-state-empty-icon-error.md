# An empty empty-state icon now throws `empty-icon`

A table whose `emptyState()` returns an empty or whitespace-only `'icon'` now
fails with the error code `larasell-admin.table.empty-state.empty-icon` instead
of `larasell-admin.table.empty-state.unknown-icon`. A blank icon is a different
mistake than a misspelled one, so it now gets its own error and fix, the same
way navigation items and table actions already report it.

## Who is affected

Apps that catch `Larasell\Admin\Exceptions\InvalidTableEmptyState` and compare
its `errorCode()` to `larasell-admin.table.empty-state.unknown-icon` for blank
icons. The exception class is unchanged, and so is the error for icon names
that are not empty. Apps that don't inspect error codes are not affected.

## How to upgrade

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
