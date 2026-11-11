# The audit log moved to larasell-dev/larasell

The `Auditor`, audit records, actors, the hash chain, pruning and `Audit::fake()` are now part of
the core package, so the core audits its own changes, wherever they come from. The admin panel
keeps the audit log page, its filters and exports. Every audited change now names an `Origin`,
who made it and how it came in, instead of the `Auditor` working it out from the signed-in
user and the queue.

## Who is affected

Apps and extensions that record their own audit records, fake them in tests, call the core's
audited actions (products, categories, sales channels, entry points, customers) or the panel's
`CreateAdmin`, `UpdateAdmin`, `DeleteAdmin`, `CreateApiToken` and `RevokeApiToken`, or set the
audit log's retention. Apps that only use the panel are not affected.

## How to upgrade

Update the imports; the classes keep their names:

```php
// Before
use Larasell\Admin\Platform\Audit\Auditor;
use Larasell\Admin\Platform\Audit\AuditRecord;
use Larasell\Admin\Platform\Audit\Subject;
use Larasell\Admin\Platform\Audit\Actors\Actor;
use Larasell\Admin\Platform\Audit\Actors\SystemActor;
use Larasell\Admin\Platform\Audit\Enums\AuditCategory;
use Larasell\Admin\Platform\Audit\Facades\Audit;

// After
use Larasell\Larasell\Audit\Auditor;
use Larasell\Larasell\Audit\AuditRecord;
use Larasell\Larasell\Audit\Subject;
use Larasell\Larasell\Audit\Actors\Actor;
use Larasell\Larasell\Audit\Actors\SystemActor;
use Larasell\Larasell\Audit\Enums\AuditCategory;
use Larasell\Larasell\Audit\Facades\Audit;
```

Pass an `Origin` to `record()` and the audited actions. In a controller, inject the current one:

```php
use Larasell\Admin\Platform\Audit\CurrentOrigin;
use Larasell\Larasell\Audit\Origin;

// Before
public function archive(Book $book, Auditor $auditor)
{
    DB::transaction(fn () => $auditor->record(new BookArchived($book)));
}

// After
public function archive(Book $book, Auditor $auditor, #[CurrentOrigin] Origin $origin)
{
    DB::transaction(fn () => $auditor->record(new BookArchived($book), $origin));
}
```

In jobs and commands, pass the `Origin` along or name it:

```php
// Before
$auditor->record(new BooksReconciled, new SystemActor('book-reconciliation'));

// After
$auditor->record(new BooksReconciled, Origin::cli(new SystemActor('book-reconciliation')));
```

`Audit::assertRecorded()` callbacks get the record and its `Origin`:

```php
// Before
Audit::assertRecorded(BookArchived::class, fn ($record, $actor, $source) => $actor->is($admin) && $source === AuditSource::Web);

// After
Audit::assertRecorded(BookArchived::class, fn ($record, Origin $origin) => $origin->actor->is($admin) && $origin->source === AuditSource::Web);
```

`UnknownActor` now implements `Actor`, so `Actor|UnknownActor` types become `Actor`.

The retention period and legal hold moved to `config/larasell/audit.php`, read from new
environment variables. The export disk stays in `config/larasell/admin/audit.php`.

```dotenv
# Before
LARASELL_ADMIN_AUDIT_RETENTION_DAYS=365
LARASELL_ADMIN_AUDIT_LEGAL_HOLD=false

# After
LARASELL_AUDIT_RETENTION_DAYS=365
LARASELL_AUDIT_LEGAL_HOLD=false
```

The `larasell_audit_log` tables and their records stay as they are: the core's migrations have
the same names as the panel's, so they don't run again.
