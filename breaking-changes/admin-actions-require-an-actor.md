# `CreateAdmin` and `DeleteAdmin` now require an actor

Adding and deleting admins now writes an audit record in the same transaction as the change. The
record says who made the change, so both actions now take that actor as a required argument.

## Who is affected

Apps that call `Larasell\Admin\Actions\CreateAdmin::handle()` or
`Larasell\Admin\Actions\DeleteAdmin::handle()` from their own code, such as a command, a job or an
SSO sync. Adding and deleting admins in the admin panel, and the `larasell:create-admin` and
`larasell:remove-admin` commands, are not affected: they pass their actor for you.

## How to upgrade

Pass who made the change. In jobs and commands, use a `SystemActor` named for what the code does.
In your own admin pages, pass the signed-in `Admin`.

```php
use Larasell\Admin\Audit\SystemActor;

// Before
app(CreateAdmin::class)->handle(['name' => 'Grace Hopper', 'email' => 'grace@example.com', 'password' => $password]);
app(DeleteAdmin::class)->handle($admin);

// After
app(CreateAdmin::class)->handle(['name' => 'Grace Hopper', 'email' => 'grace@example.com', 'password' => $password], new SystemActor('sso-sync'));
app(DeleteAdmin::class)->handle($admin, new SystemActor('sso-sync'));
```

If the actor truly can't be known, pass an `UnknownActor` with the reason, e.g.
`new UnknownActor('Imported from the previous shop system.')`.

Both actions now also throw `Larasell\Admin\Exceptions\AuditFailed` when the audit record can't be
stored. No admin is then added or deleted, and a deleted admin keeps their avatar.
