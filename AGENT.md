# Agent notes

## Code layout

The panel is a modular monolith. Each feature is a module at the top of `src/`:
`ApiToken`, `Category`, `Customer`, `Order`, `Product` and `Team`. What modules and
apps plug into lives under `src/Platform/`: `Panel`, `Auth`, `Navigation`, `Tables`,
`Search`, `Audit`, `ContentLocale`, `InterfaceLocale` and `Support`.

Every module has the same layout, with only the folders it needs:
`<Module>ServiceProvider.php` (config, bindings, routes, navigation, searches),
`Actions/` (with their payloads), `AuditRecords/`, `Console/`, `Data/` (page props
and their parts), `Enums/`, `Events/` (what it broadcasts), `Exceptions/`, `Http/{Controllers,Middleware,Requests}/`,
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

## Live updates

Props can reload in every signed-in admin's browser when the server says they changed, with
Inertia's live props: `Inertia::once(...)->live(on: SomeEvent::class)`. The event implements
`ShouldBroadcast` (and `ShouldDispatchAfterCommit` when it follows a write) on a `PrivateChannel`,
and the module allows admins on it with `Broadcast::channel(..., ['guards' => ['larasell-admin']])`.
Echo authorizes at `/admin/broadcasting/auth`. The browser connects only when the app broadcasts
with Reverb or Pusher (`Platform/Panel/LiveUpdates`); otherwise pages update on the next visit.
See `SalesChannel` for the sidebar's channel list. The workbench needs the `reverb` service in
`.amp/services.yaml` running next to `admin`. To try it through one portal, run
`node workbench/live-proxy.mjs 8001` (the panel and Reverb on one origin), expose port 8001, and
set `LARASELL_ADMIN_LIVE_HOST=<portal host>`, `LARASELL_ADMIN_LIVE_PORT=443` and
`LARASELL_ADMIN_LIVE_SCHEME=https` in `vendor/orchestra/testbench-core/laravel/.env`, since
`testbench serve` passes few environment variables on.

## Interface language (i18n)

The panel's own text, its buttons, menus and messages, is shown in the interface language. Each
admin picks it (command menu → "Change interface language"). It is separate from the content
locale, which products and categories are edited in. Both use full codes such as `de-DE`, in the
frontend's files and the server's alike.

- **Server:** `src/Platform/InterfaceLocale`. `SetInterfaceLocale` asks the bound
  `InterfaceLocaleResolver` (a strategy; `CookieInterfaceLocaleResolver` by default) for one of
  `AvailableInterfaceLocales` and sets the app locale. `<html lang dir>` comes from it, and the
  frontend loads its translations from `lang`. These are the panel's own classes, not the
  content locales of `larasell-dev/larasell`.
- **Frontend text** is translated with [fbtee](https://fbtee.dev): `<fbt desc="…">` in JSX,
  `fbs("…", "…")` for plain strings such as `aria-label` and command labels. The description is
  all a translator sees, so say where the text shows. Use `<fbt:param>`/`fbs.param()` for values
  instead of concatenating, and `<fbt:plural>` for counts. Never translate at module level: it runs
  before the translations load. Use a function, like `themeLabel()`.
- **Server text** uses Laravel's JSON translations, keyed by the English text: `__('Products')`.
  Text registered while the app boots, such as navigation and search labels, is marked with
  `Translate::later('Products')` and translated with `__()` when it's shown. Route titles
  (`->withHead(title: Translate::later('Dashboard'))`) are translated by `SetInterfaceLocale`.
  Table action labels are given the same way, `Action::make(Translate::later('Delete'))`: the
  action translates its label when shown and keeps the English one for its URL slug.
- After adding or changing text, run `npm run translations:extract`. New frontend text gets
  `"status": "new"` in `resources/translations/<code>.json`; translate it and remove the status.
  New server text gets `""` in `lang/<code>.json`; fill it in. CI fails when the files are out of
  date. Untranslated text shows in English. Add a language with
  `npm run translations:extract -- fr-FR` and list it in `PanelInterfaceLocales::TRANSLATED`.
- **Pseudo-languages** are offered only when `APP_ENV=local`: `en-XA` (accented and about 40%
  longer, in brackets) and `ar-XB` (right to left). They are made up from the English text, by
  `npm run translations:compile` for the frontend and at runtime for the server. Pick one to spot
  hard-coded text, which stays plain English, and layouts that break.

## Arrange, act, assert

Every test, Pest and Vitest alike, marks what it sets up, what it exercises and what it checks
with `// Arrange`, `// Act` and `// Assert` comments, each on its own line above its section, in
that order. Leave out `// Arrange` when a test sets nothing up. Sections that can't be split share
one comment, such as `// Act & Assert` for a request with its assertions chained on, or for
`expect(fn () => …)->toThrow()`. `lint/PHPStan/ArrangeActAssertRule.php` (in `composer phpstan`)
and `lint/eslint/larasell-admin.mjs` (in `npm run lint`) enforce it.

## Best-before markers

A comment like `BEST-BEFORE YYYY-MM-DD: what to revisit` is a to-do with a deadline, in any
file and language. The note can continue on the following comment lines. After the date,
the `Best before` CI job (`npm run best-before`) fails until the work is done or the date
moves. See `scripts/best-before.mjs`.

## Breaking changes

Every breaking change needs an upgrade note in `breaking-changes/` and a `!` in
the pull request title. Follow `breaking-changes/AGENT.md` when writing one.
