# Agent notes

## Code layout

The panel is a modular monolith. Each feature is a module at the top of `src/`:
`ApiToken`, `Category`, `Customer`, `Order`, `Product` and `Team`. What modules and
apps plug into lives under `src/Platform/`: `Panel`, `Auth`, `Navigation`, `Tables`,
`Search`, `Audit`, `ContentLocale` and `Support`.

Every module has the same layout, with only the folders it needs:
`<Module>ServiceProvider.php` (config, bindings, routes, navigation, searches),
`Actions/` (with their payloads), `AuditRecords/`, `Console/`, `Data/` (page props
and their parts), `Enums/`, `Exceptions/`, `Http/{Controllers,Middleware,Requests}/`,
`Models/`, `Rules/`, `Search/` and `Tables/`. The frontend follows the same names:
`resources/js/Components/<Module>/`, and tests in `tests/Feature/<Module>/` or
`tests/Feature/Platform/<Area>/`.

The platform never uses a module, and a module uses another one only when
`tests/Arch/ModulesTest.php` lists it as what it builds on. When a module needs the
platform to know about it, it registers itself, e.g. `Auditor::actorType()`.

## Dev-only endpoints (`/admin/__dev`)

The admin panel registers a few shortcut routes **only when the app runs in the
`local` environment** (`APP_ENV=local`). In any other environment (production,
staging, testing, …) they are never registered and return 404. See
`routes/web.php` and `src/Platform/Panel/Http/Controllers/Dev/`;
`tests/Feature/Platform/Panel/DevRoutesTest.php` guards that they stay local-only.

The admin path prefix is configurable (`LARASELL_ADMIN_PATH`, default `admin`).

| Route                     | What it does                                                                                                                                                   |
| ------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `GET /admin/__dev`        | JSON list of every `__dev` route (`name`, `methods`, `uri`).                                                                                                   |
| `GET /admin/__dev/login`  | Logs in as the first admin, creating a `dev@example.com` admin if none exists. `?email=` picks a specific admin (404 if missing). Redirects to the admin home. |
| `GET /admin/__dev/logout` | Logs out and redirects to the login page.                                                                                                                      |

Both login and logout accept `?redirect=/some/path` to land somewhere else
(same-site paths only).

The workbench playground (`vendor/bin/testbench serve`, or the `admin` service
in `.amp/services.yaml`) runs with `APP_ENV=local`, so these are available there.

### Use them yourself

You (the agent) can use these endpoints for faster testing and debugging, for
example to skip the login form in a browser session or with curl:

```sh
curl -c /tmp/cookies http://localhost:8000/admin/__dev/login
curl -b /tmp/cookies http://localhost:8000/admin/profile
```

In `agent-browser`, open `/admin/__dev/login?redirect=/admin/profile` to land on
a page as a signed-in admin. Hit `/admin/__dev` to discover any newly added
shortcuts. When adding a new dev shortcut, put it in the `__dev` group in
`routes/web.php` so it is listed automatically and inherits the local-only guard.

## Best-before markers

A comment like `BEST-BEFORE YYYY-MM-DD: what to revisit` is a to-do with a deadline, in any
file and language. The note can continue on the following comment lines. After the date,
the `Best before` CI job (`npm run best-before`) fails until the work is done or the date
moves. See `scripts/best-before.mjs`.

## Breaking changes

Every breaking change needs an upgrade note in `breaking-changes/` and a `!` in
the pull request title. Follow `breaking-changes/AGENT.md` when writing one.
