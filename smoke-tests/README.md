# Smoke tests

`smoke-tests/run.sh` checks that a developer can install this package into a brand-new Laravel
application by following the official guides, and that the panel then works in a real browser.
It runs in CI as the `Smoke tests` job of every pull request.

## What it does

1. **Package.** Clones `SMOKE_PACKAGE_REPO` at `SMOKE_PACKAGE_REF`, builds the admin assets with
   `npm ci && npm run build`, and commits them on a throwaway `smoke-test` branch of that clone.
   Only release pull requests commit built assets, so a branch carries the last release's assets;
   rebuilding them reproduces what a release of the branch would ship.
2. **Laravel app.** `laravel new example-store --database=sqlite --pest --npm` in a temporary
   directory.
3. **Larasell** ([guide](https://larasell.dev/docs/installation)). `composer require
   larasell-dev/larasell`, `php artisan migrate`, `npm install`.
4. **Larasell Admin** ([guide](https://larasell.dev/docs/admin/installation)). Adds
   `@php artisan larasell:link --ansi` to `post-autoload-dump` after package discovery, requires
   `larasell-dev/admin` from the clone at the exact commit, runs the migrations, and creates an
   admin with `larasell:create-admin`. Fails if Composer resolved any other commit, if the assets
   were not linked, or if migrations are pending.
5. **Environment.** Sets `APP_URL`, and `LARASELL_ADMIN_PATH` when `SMOKE_ADMIN_PATH` isn't
   `admin`.
6. **Browser tooling.** Installs `pestphp/pest-plugin-browser` and Playwright's Chromium into the
   app and copies `smoke-tests/tests/Browser` into it.
7. **Server.** Starts `php artisan serve` and waits until the login page answers.
8. **Tests.** `vendor/bin/pest tests/Browser --ci --trace`.
9. **Cleanup.** Stops the server and deletes the temporary directory, also when a step fails.

Every step prints a `==> [smoke]` header; a failure names the step and prints the end of the
server log and `storage/logs/laravel.log`.

## Running it locally

Prerequisites: `php` (8.4 or newer, with `pdo_sqlite` and `sockets`), `composer`, the `laravel`
CLI, Node 22 with `npm`, `git`, and `curl`. The Larasell installer
(<https://larasell.dev/docs/installation#prerequisites>) installs PHP, Composer, and the Laravel
CLI; CI uses it too. Chromium needs its system libraries; set `SMOKE_PLAYWRIGHT_WITH_DEPS=1` to let
Playwright install them (needs sudo).

```sh
smoke-tests/run.sh
```

The package is cloned from your local repository, so commit the changes you want to test first.

| Variable                     | Default                         | Purpose                                                  |
| ---------------------------- | ------------------------------- | -------------------------------------------------------- |
| `SMOKE_PACKAGE_REPO`         | this repository                 | Git URL or path to clone the package from                |
| `SMOKE_PACKAGE_REF`          | `HEAD`                          | Branch, tag, or commit to test                           |
| `SMOKE_LARAVEL_VERSION`      | whatever `laravel new` installs | `laravel/framework` constraint, for example `13.23.*`    |
| `SMOKE_PORT`                 | `8000`                          | Port for `php artisan serve`                             |
| `SMOKE_ADMIN_PATH`           | `admin`                         | Admin path, written to `LARASELL_ADMIN_PATH`             |
| `SMOKE_READY_TIMEOUT`        | `60`                            | Seconds to wait for the server                           |
| `SMOKE_WORK_DIR`             | a new temporary directory       | Where the clone, app, and server log go                  |
| `SMOKE_ARTIFACTS_DIR`        | none                            | Copies screenshots, traces, and logs here before cleanup |
| `SMOKE_KEEP`                 | `0`                             | `1` keeps the work directory for inspection              |
| `SMOKE_PLAYWRIGHT_WITH_DEPS` | unset                           | Non-empty installs Chromium's system libraries           |

To test a pushed branch without a local checkout:

```sh
SMOKE_PACKAGE_REPO=https://github.com/Larasell-dev/admin.git SMOKE_PACKAGE_REF=my-branch smoke-tests/run.sh
```

## Troubleshooting

- **A browser test failed.** Pest saves a screenshot to `tests/Browser/Screenshots` and a
  Playwright trace to `tests/Browser/Traces`. In CI, download the `smoke-tests` artifact and drop
  the trace on <https://trace.playwright.dev>; locally, run with `SMOKE_ARTIFACTS_DIR=…` or
  `SMOKE_KEEP=1` and open it with `npx playwright show-trace <trace>`.
- **The server never became ready.** The script prints the server log; `SMOKE_KEEP=1` keeps the
  app so you can run `php artisan serve` in it yourself.
- **Port already in use.** Pick another with `SMOKE_PORT`.
- **Too many login attempts (429).** The login route allows five attempts a minute. The tests
  sign in twice; keep new tests within that budget.

## Laravel versions

The package requires `laravel/framework` `^13.23`, so CI tests the latest Laravel 13 that
`laravel new` installs. Once a second major version is supported, add a matrix over
`SMOKE_LARAVEL_VERSION` (for example the lowest supported minor and the latest) to the CI job.
