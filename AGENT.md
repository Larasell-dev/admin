# Agent notes

## Dev-only endpoints (`/admin/__dev`)

The admin panel registers a few shortcut routes **only when the app runs in the
`local` environment** (`APP_ENV=local`). In any other environment (production,
staging, testing, …) they are never registered and return 404. See
`routes/web.php` and `src/Http/Controllers/Dev/`;
`tests/Feature/DevRoutesTest.php` guards that they stay local-only.

The admin path prefix is configurable (`LARASELL_ADMIN_PATH`, default `admin`).

| Route | What it does |
| --- | --- |
| `GET /admin/__dev` | JSON list of every `__dev` route (`name`, `methods`, `uri`). |
| `GET /admin/__dev/login` | Logs in as the first admin, creating a `dev@example.com` admin if none exists. `?email=` picks a specific admin (404 if missing). Redirects to the admin home. |
| `GET /admin/__dev/logout` | Logs out and redirects to the login page. |

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
