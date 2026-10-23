#!/usr/bin/env bash
# End-to-end smoke test: installs larasell-dev/admin into a brand-new Laravel application the way
# https://larasell.dev/docs/installation and https://larasell.dev/docs/admin/installation describe,
# serves it with `php artisan serve`, and runs the Pest browser tests in smoke-tests/tests/Browser
# against it. See smoke-tests/README.md for prerequisites and configuration.
set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

# --- Configuration ---
package_repo="${SMOKE_PACKAGE_REPO:-$repo_root}"
package_ref="${SMOKE_PACKAGE_REF:-HEAD}"
laravel_version="${SMOKE_LARAVEL_VERSION:-}"
port="${SMOKE_PORT:-8000}"
admin_path="${SMOKE_ADMIN_PATH:-admin}"
ready_timeout="${SMOKE_READY_TIMEOUT:-60}"
work_dir="${SMOKE_WORK_DIR:-$(mktemp -d "${TMPDIR:-/tmp}/larasell-admin-smoke.XXXXXX")}"
artifacts_dir="${SMOKE_ARTIFACTS_DIR:-}"
keep="${SMOKE_KEEP:-0}"

package_dir="$work_dir/package"
app_dir="$work_dir/example-store"
server_log="$work_dir/server.log"
base_url="http://127.0.0.1:$port"
admin_name="Ada Lovelace"
admin_email="ada@example.com"
# CreateAdmin requires 12+ characters with mixed case, numbers, and symbols.
admin_password="Smoke-Test-$(date +%s)-$RANDOM"
smoke_branch="smoke-test"
server_pid=""
current_step="startup"

step() {
    current_step="$*"
    echo
    echo "==> [smoke] $(date -u +%H:%M:%S) $*"
}

fail() {
    echo "::error::[smoke] $*" >&2
    exit 1
}

cleanup() {
    local status=$?

    if [ -n "$server_pid" ] && kill -0 "$server_pid" 2>/dev/null; then
        # artisan serve forks PHP's built-in server; stop the whole process group.
        kill -- "-$server_pid" 2>/dev/null || kill "$server_pid" 2>/dev/null || true
        wait "$server_pid" 2>/dev/null || true
    fi

    if [ "$status" -ne 0 ]; then
        echo "::error::[smoke] Failed during: $current_step (exit $status)" >&2

        if [ -s "$server_log" ]; then
            echo "--- server log (last 100 lines) ---" >&2
            tail -n 100 "$server_log" >&2
        fi

        if [ -s "$app_dir/storage/logs/laravel.log" ]; then
            echo "--- storage/logs/laravel.log (last 100 lines) ---" >&2
            tail -n 100 "$app_dir/storage/logs/laravel.log" >&2
        fi
    fi

    if [ -n "$artifacts_dir" ]; then
        mkdir -p "$artifacts_dir"
        [ -f "$server_log" ] && cp "$server_log" "$artifacts_dir/"
        [ -f "$app_dir/storage/logs/laravel.log" ] && cp "$app_dir/storage/logs/laravel.log" "$artifacts_dir/"
        for directory in Screenshots Traces; do
            if [ -d "$app_dir/tests/Browser/$directory" ]; then
                mkdir -p "$artifacts_dir/$directory"
                cp -R "$app_dir/tests/Browser/$directory/." "$artifacts_dir/$directory/"
            fi
        done
    fi

    if [ "$keep" = "1" ]; then
        echo "[smoke] Kept the work directory: $work_dir"
    else
        rm -rf "$work_dir"
    fi

    exit "$status"
}
trap cleanup EXIT

# --- 0. Prerequisites ---
step "Checking prerequisites"
for tool in php composer laravel node npm git curl; do
    command -v "$tool" >/dev/null 2>&1 || fail "$tool is not on the PATH. See smoke-tests/README.md."
done
php -v | head -n 1
composer --version 2>/dev/null | head -n 1
laravel --version
echo "Node $(node --version), npm $(npm --version)"
echo "Work directory: $work_dir"
[ ! -e "$app_dir" ] || fail "$app_dir already exists. Point SMOKE_WORK_DIR at an empty directory."
mkdir -p "$work_dir"

# --- 1. Package under test ---
# Built assets are only committed by release pull requests (see release-please.yml), so a branch
# carries the last release's assets, not its own. Rebuild and commit them on a throwaway branch of
# a fresh clone, as the release job does, so Composer installs what a release of this code ships.
step "Preparing larasell-dev/admin from $package_repo at $package_ref"
git clone --quiet --no-checkout "$package_repo" "$package_dir"
git -C "$package_dir" checkout --quiet --detach "$package_ref"
tested_sha="$(git -C "$package_dir" rev-parse HEAD)"
echo "Code under test: $tested_sha"
git -C "$package_dir" checkout --quiet -B "$smoke_branch"

(cd "$package_dir" && npm ci --no-audit --no-fund --loglevel=error && npm run build)
[ -f "$package_dir/public/vendor/larasell-admin/manifest.json" ] || fail "npm run build did not produce public/vendor/larasell-admin/manifest.json"

git -C "$package_dir" add --all --force public/vendor/larasell-admin
if ! git -C "$package_dir" diff --cached --quiet; then
    git -C "$package_dir" \
        -c user.name="Larasell smoke test" -c user.email="smoke-test@larasell.dev" \
        commit --quiet --no-verify --message "build: bundle admin assets for the smoke test"
fi
package_sha="$(git -C "$package_dir" rev-parse HEAD)"
echo "Installable commit: $package_sha (code under test $tested_sha plus built assets)"

# --- 2. Fresh Laravel application (https://larasell.dev/docs/installation) ---
step "Creating a new Laravel application"
(cd "$work_dir" && laravel new example-store --no-interaction --database=sqlite --pest --npm --no-boost)
cd "$app_dir"

if [ -n "$laravel_version" ]; then
    step "Pinning laravel/framework to $laravel_version"
    composer require "laravel/framework:$laravel_version" --with-all-dependencies --no-interaction --no-progress
fi
php artisan --version

# --- 3. Larasell (https://larasell.dev/docs/installation) ---
step "Installing larasell-dev/larasell"
composer require larasell-dev/larasell --no-interaction --no-progress
php artisan migrate --no-interaction

# laravel new --npm already ran npm install; the guide's next step is npm install.
npm install --no-audit --no-fund --loglevel=error

# --- 4. Larasell Admin (https://larasell.dev/docs/admin/installation) ---
step "Adding larasell:link to the post-autoload-dump scripts"
php -r '
    $file = "composer.json";
    $composer = json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
    $scripts = $composer["scripts"]["post-autoload-dump"] ?? [];
    $discover = array_search("@php artisan package:discover --ansi", $scripts, true);
    if ($discover === false) {
        fwrite(STDERR, "composer.json has no package:discover post-autoload-dump script.\n");
        exit(1);
    }
    array_splice($scripts, $discover + 1, 0, ["@php artisan larasell:link --ansi"]);
    $composer["scripts"]["post-autoload-dump"] = $scripts;
    file_put_contents($file, json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
'

step "Installing larasell-dev/admin from commit $package_sha"
# A VCS repository declared in composer.json takes precedence over Packagist, so the published
# releases cannot satisfy this requirement.
composer config repositories.larasell-admin vcs "$package_dir"
composer require "larasell-dev/admin:dev-$smoke_branch#$package_sha" --no-interaction --no-progress

installed_sha="$(composer show larasell-dev/admin --format=json | php -r 'echo json_decode(stream_get_contents(STDIN), true)["source"]["reference"] ?? "";')"
echo "Installed larasell-dev/admin reference: $installed_sha"
[ "$installed_sha" = "$package_sha" ] || fail "Composer installed $installed_sha instead of $package_sha"
[ -f public/vendor/larasell-admin/manifest.json ] || fail "larasell:link did not link the admin assets into public/vendor/larasell-admin"

step "Running the migrations"
php artisan migrate --no-interaction
migrations="$(php artisan migrate:status --no-ansi)"
echo "$migrations"
grep -q "larasell_admin_users" <<<"$migrations" || fail "The admin migrations were not loaded"
! grep -q "Pending" <<<"$migrations" || fail "Migrations are still pending after php artisan migrate"

step "Creating an admin"
php artisan larasell:create-admin --name="$admin_name" --email="$admin_email" --password="$admin_password"
admins="$(php artisan larasell:list-admins --no-ansi)"
echo "$admins"
grep -qF "$admin_email" <<<"$admins" || fail "larasell:list-admins does not list $admin_email"

# --- 5. Environment ---
step "Configuring the environment"
php -r '
    [, $url, $path] = $argv;
    $env = file_get_contents(".env");
    $env = preg_replace("/^APP_URL=.*$/m", "APP_URL=$url", $env);
    if ($path !== "admin") {
        $env .= PHP_EOL."LARASELL_ADMIN_PATH=$path".PHP_EOL;
    }
    file_put_contents(".env", $env);
' "$base_url" "$admin_path"
php artisan config:clear --no-interaction

# --- 6. Browser test dependencies (https://pestphp.com/docs/browser-testing) ---
step "Installing the Pest browser plugin and Playwright"
# ^5.1 adds --trace. --with-all-dependencies lets Composer update Pest to the version it needs.
composer require "pestphp/pest-plugin-browser:^5.1" --dev --with-all-dependencies --no-interaction --no-progress
npm install playwright@latest --no-audit --no-fund --loglevel=error
npx playwright install ${SMOKE_PLAYWRIGHT_WITH_DEPS:+--with-deps} chromium
printf '%s\n' /tests/Browser/Screenshots /tests/Browser/Traces >> .gitignore
cp -R "$repo_root/smoke-tests/tests/Browser" tests/Browser

# --- 7. Server ---
step "Starting php artisan serve on $base_url"
# setsid gives the server its own process group, so cleanup can stop PHP's forked workers too.
setsid php artisan serve --host=127.0.0.1 --port="$port" --no-reload >"$server_log" 2>&1 &
server_pid=$!

deadline=$((SECONDS + ready_timeout))
until curl --silent --fail --output /dev/null "$base_url/$admin_path/login"; do
    kill -0 "$server_pid" 2>/dev/null || fail "php artisan serve exited before it became ready"
    [ "$SECONDS" -lt "$deadline" ] || fail "$base_url/$admin_path/login did not respond within ${ready_timeout}s"
    sleep 1
done
echo "Ready: $base_url/$admin_path/login"

# --- 8. Browser tests ---
step "Running the browser tests"
SMOKE_BASE_URL="$base_url" \
    SMOKE_ADMIN_PATH="$admin_path" \
    SMOKE_ADMIN_NAME="$admin_name" \
    SMOKE_ADMIN_EMAIL="$admin_email" \
    SMOKE_ADMIN_PASSWORD="$admin_password" \
    vendor/bin/pest tests/Browser --ci --trace

kill -0 "$server_pid" 2>/dev/null || fail "php artisan serve exited during the browser tests"
current_step="done"
echo
echo "[smoke] larasell-dev/admin $tested_sha passed the smoke tests."
