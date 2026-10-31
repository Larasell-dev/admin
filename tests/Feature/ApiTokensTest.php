<?php

declare(strict_types=1);

use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Larasell\Admin\Models\Admin;
use Larasell\Admin\Models\ApiToken;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-30 12:00:00'));

    $this->ada = Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);

    // What an extension's API does: its routes take the middleware alias and read the user.
    Route::middleware(['api', 'larasell.api'])->get('api/whoami', fn (Request $request) => [
        'class' => $request->user()::class,
        'id' => $request->user()->getAuthIdentifier(),
        'name' => $request->user()->name,
    ]);
});

/**
 * Creates a token through the settings page and returns the plaintext token it shows once.
 *
 * @param  array<string, mixed>  $input
 */
function createApiToken(array $input = []): string
{
    $response = test()->actingAs(test()->ada, 'larasell-admin')
        ->post(route('larasell.admin.settings.api-tokens.store'), [
            'name' => 'ERP sync',
            'description' => 'Pushes stock levels every night.',
            'expires_in' => '3_months',
            ...$input,
        ])
        ->assertRedirect(route('larasell.admin.settings.api-tokens.index'));

    $token = test()->get(route('larasell.admin.settings.api-tokens.index'))->inertiaPage()['flash']['apiToken']['plainTextToken'];

    // The session and admin guard stay out of API requests.
    test()->flushSession();
    app('auth')->forgetGuards();

    return $token;
}

function callApi(?string $token): TestResponse
{
    return test()->getJson('api/whoami', $token === null ? [] : ['Authorization' => 'Bearer '.$token]);
}

it('lists the API tokens in the settings', function () {
    createApiToken();
    createApiToken(['name' => 'Analytics', 'description' => null, 'expires_in' => '1_month']);

    $this->actingAs($this->ada, 'larasell-admin')
        ->get(route('larasell.admin.settings.api-tokens.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('ApiToken/Index')
            ->where('createUrl', route('larasell.admin.settings.api-tokens.create'))
            ->where('apiTokens.title', 'API tokens')
            ->where('apiTokens.columns', [
                ['name' => 'name', 'label' => 'Name', 'type' => 'text'],
                ['name' => 'description', 'label' => 'Description', 'type' => 'text'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'badge'],
                ['name' => 'expires_at', 'label' => 'Expires (UTC)', 'type' => 'text'],
                ['name' => 'last_used_at', 'label' => 'Last used', 'type' => 'text'],
            ])
            ->where('apiTokens.rows.0.name', 'Analytics')
            ->where('apiTokens.rows.0.description', null)
            ->where('apiTokens.rows.0.status', 'Active')
            ->where('apiTokens.rows.0._variants.status', 'success')
            ->where('apiTokens.rows.0.expires_at', 'Nov 30, 2026 23:59')
            ->where('apiTokens.rows.0.last_used_at', 'Never')
            ->where('apiTokens.rows.1.name', 'ERP sync')
            ->where('apiTokens.rows.1.description', 'Pushes stock levels every night.')
            ->where('apiTokens.rows.1.expires_at', 'Jan 30, 2027 23:59'));
});

it('marks expired tokens as expired in the list', function () {
    createApiToken(['name' => 'Old sync', 'expires_in' => '1_week']);
    createApiToken(['name' => 'ERP sync', 'expires_in' => '1_month']);

    // A second after the week ends: the first token has just expired, the second hasn't.
    $this->travelTo(Carbon::parse('2026-11-07 00:00:01'));

    $this->actingAs($this->ada, 'larasell-admin')
        ->get(route('larasell.admin.settings.api-tokens.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('apiTokens.rows.0.name', 'ERP sync')
            ->where('apiTokens.rows.0.status', 'Active')
            ->where('apiTokens.rows.1.name', 'Old sync')
            ->where('apiTokens.rows.1.status', 'Expired')
            ->where('apiTokens.rows.1._variants.status', 'danger'));
});

it('ends tokens at the end of the day in the app\'s time zone, and says which', function () {
    // A Berlin shop: the token must not stop at midnight UTC, an hour into the next Berlin day.
    config(['app.timezone' => 'Europe/Berlin']);
    date_default_timezone_set('Europe/Berlin');

    try {
        $this->travelTo(Carbon::parse('2026-10-30 09:00:00'));
        $plainTextToken = createApiToken(['expires_in' => '1_month']);

        $this->actingAs($this->ada, 'larasell-admin')
            ->get(route('larasell.admin.settings.api-tokens.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('apiTokens.columns.3.label', 'Expires (Europe/Berlin)')
                ->where('apiTokens.rows.0.expires_at', 'Nov 30, 2026 23:59'));
        $this->flushSession();
        app('auth')->forgetGuards();

        $this->travelTo(Carbon::parse('2026-11-30 23:59:00'));
        callApi($plainTextToken)->assertOk();

        $this->travelTo(Carbon::parse('2026-12-01 00:00:01'));
        callApi($plainTextToken)->assertUnauthorized();
    } finally {
        date_default_timezone_set('UTC');
    }
});

it('adds API tokens to the settings navigation', function () {
    $this->actingAs($this->ada, 'larasell-admin')
        ->get(route('larasell.admin.settings.api-tokens.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('navigation', fn ($navigation) => collect($navigation)
                ->firstWhere('label', 'Settings')['children'][1]['url'] === route('larasell.admin.settings.api-tokens.index')));
});

it('opens the new API token page with a choice of expiry periods, 3 months by default', function () {
    $this->actingAs($this->ada, 'larasell-admin')
        ->get(route('larasell.admin.settings.api-tokens.create'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('ApiToken/Create')
            ->where('storeUrl', route('larasell.admin.settings.api-tokens.store'))
            ->where('indexUrl', route('larasell.admin.settings.api-tokens.index'))
            ->where('expiryOptions', [
                ['value' => '1_week', 'label' => 'After 1 week'],
                ['value' => '1_month', 'label' => 'After 1 month'],
                ['value' => '3_months', 'label' => 'After 3 months'],
                ['value' => '6_months', 'label' => 'After 6 months'],
                ['value' => '9_months', 'label' => 'After 9 months'],
            ])
            ->where('defaultExpiresIn', '3_months'));
});

it('creates a token that authenticates API requests as the API token', function () {
    $plainTextToken = createApiToken();

    $apiToken = ApiToken::query()->sole();

    expect($apiToken->name)->toBe('ERP sync')
        ->and($apiToken->description)->toBe('Pushes stock levels every night.')
        ->and($apiToken->createdBy->is($this->ada))->toBeTrue()
        ->and($plainTextToken)->toMatch('/^larasell_[A-Za-z0-9]{40}[a-f0-9]{8}$/');

    callApi($plainTextToken)
        ->assertOk()
        ->assertExactJson(['class' => ApiToken::class, 'id' => $apiToken->id, 'name' => 'ERP sync']);
});

it('shows the plaintext token only once', function () {
    createApiToken();

    $this->actingAs($this->ada, 'larasell-admin')
        ->get(route('larasell.admin.settings.api-tokens.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page->missingFlash('apiToken'));
});

it('stores only a hash of the token', function () {
    $plainTextToken = createApiToken();

    expect(DB::table('larasell_api_tokens')->sole()->token_hash)
        ->toBe(hash('sha256', $plainTextToken));
});

it('accepts a token until the end of the day its period ends', function (string $today, string $expiresIn, string $lastDay) {
    $this->travelTo(Carbon::parse($today.' 09:00:00'));
    $plainTextToken = createApiToken(['expires_in' => $expiresIn]);

    $this->travelTo(Carbon::parse($lastDay.' 23:59:00'));
    callApi($plainTextToken)->assertOk();

    $this->travelTo(Carbon::parse($lastDay)->addDay()->setTime(0, 0, 1));
    callApi($plainTextToken)->assertUnauthorized();
})->with([
    '1 week' => ['2026-10-30', '1_week', '2026-11-06'],
    '1 month' => ['2026-10-30', '1_month', '2026-11-30'],
    '6 months' => ['2026-10-30', '6_months', '2027-04-30'],
    '9 months' => ['2026-10-30', '9_months', '2027-07-30'],
    // Not March 3: a month from the 31st ends with the shorter month.
    '1 month from the 31st' => ['2027-01-31', '1_month', '2027-02-28'],
]);

it('records when a token was last used', function () {
    $plainTextToken = createApiToken();

    $this->travelTo(Carbon::parse('2026-11-02 08:00:00'));
    callApi($plainTextToken)->assertOk();

    expect(ApiToken::query()->sole()->last_used_at->toDateTimeString())->toBe('2026-11-02 08:00:00');
});

it('records the last use at most once a minute', function () {
    // A polling integration shouldn't turn every read into a write.
    $plainTextToken = createApiToken();
    $lastUsedAt = fn (): string => ApiToken::query()->sole()->last_used_at->toDateTimeString();

    $this->travelTo(Carbon::parse('2026-11-02 08:00:00'));
    callApi($plainTextToken)->assertOk();

    $this->travelTo(Carbon::parse('2026-11-02 08:00:59'));
    callApi($plainTextToken)->assertOk();
    expect($lastUsedAt())->toBe('2026-11-02 08:00:00');

    $this->travelTo(Carbon::parse('2026-11-02 08:01:01'));
    callApi($plainTextToken)->assertOk();
    expect($lastUsedAt())->toBe('2026-11-02 08:01:01');
});

it('stops answering an address after 30 failed attempts in a minute', function () {
    $plainTextToken = createApiToken();
    $from = fn (string $ip, ?string $token) => test()->withServerVariables(['REMOTE_ADDR' => $ip])->getJson(
        'api/whoami',
        $token === null ? [] : ['Authorization' => 'Bearer '.$token],
    );

    foreach (range(1, 30) as $attempt) {
        $from('203.0.113.7', 'larasell_wrong')->assertUnauthorized();
    }

    // Checked before the token is looked up, so even a valid token from there waits.
    $from('203.0.113.7', 'larasell_wrong')->assertTooManyRequests()->assertHeader('Retry-After');
    $from('203.0.113.7', $plainTextToken)->assertTooManyRequests();

    // Other addresses, and successful requests before the limit, aren't counted.
    $from('198.51.100.1', $plainTextToken)->assertOk();
    $from('198.51.100.1', 'larasell_wrong')->assertUnauthorized();

    $this->travel(61)->seconds();
    $from('203.0.113.7', $plainTextToken)->assertOk();
});

it('rejects a new token without a name or with an unknown expiry', function (array $input, array $errors) {
    $this->actingAs($this->ada, 'larasell-admin')
        ->from(route('larasell.admin.settings.api-tokens.create'))
        ->post(route('larasell.admin.settings.api-tokens.store'), [
            'name' => 'ERP sync',
            'expires_in' => '3_months',
            ...$input,
        ])
        ->assertRedirect(route('larasell.admin.settings.api-tokens.create'))
        ->assertSessionHasErrors($errors);

    expect(ApiToken::query()->count())->toBe(0);
})->with([
    'no name' => [['name' => ''], ['name']],
    'no expiry' => [['expires_in' => null], ['expires_in']],
    'an unknown period' => [['expires_in' => '2_years'], ['expires_in']],
    'a date' => [['expires_in' => '2027-01-28'], ['expires_in']],
]);

it('revokes a token after its name is typed to confirm', function () {
    $plainTextToken = createApiToken();

    $revoke = $this->actingAs($this->ada, 'larasell-admin')
        ->get(route('larasell.admin.settings.api-tokens.index'))
        ->inertiaProps('apiTokens.rows.0._actions.0');

    expect($revoke)->toMatchArray(['label' => 'Revoke', 'destructive' => true])
        ->and($revoke['confirm']['confirmationText'])->toBe('ERP sync');

    $this->post($revoke['url'], ['confirmation' => 'ERP sync'])->assertRedirect();

    expect(ApiToken::query()->count())->toBe(0);

    $this->flushSession();
    app('auth')->forgetGuards();
    callApi($plainTextToken)->assertUnauthorized();
});

it('keeps tokens when the admin who created them is deleted', function () {
    $plainTextToken = createApiToken();
    $alan = Admin::create(['name' => 'Alan Turing', 'email' => 'alan@example.com', 'password' => 'password']);

    $this->ada->delete();

    expect(ApiToken::query()->sole()->created_by)->toBeNull();
    callApi($plainTextToken)->assertOk();
    expect($alan->exists)->toBeTrue();
});

it('rejects API requests without a valid token', function (?string $token) {
    callApi($token)->assertUnauthorized();
})->with([
    'no token' => [null],
    'unknown token' => ['larasell_unknown'],
    'malformed token' => ['not-a-token'],
]);

it('rejects a token with a tampered secret', function () {
    $plainTextToken = createApiToken();
    $tampered = substr($plainTextToken, 0, -1).(str_ends_with($plainTextToken, 'a') ? 'b' : 'a');

    callApi($tampered)->assertUnauthorized();
});

it('keeps out of the app\'s own personal access tokens', function () {
    // An app may use Sanctum for its own users; API tokens neither share nor create its table.
    expect(Schema::hasTable('personal_access_tokens'))->toBeFalse();
});

it('ignores a signed-in session on API requests', function () {
    // auth:sanctum would accept this session user before looking at any token.
    $this->actingAs($this->ada, 'larasell-admin');

    callApi(null)->assertUnauthorized();
});

it('answers a request without a valid token with a 401, never a redirect', function () {
    // A plain request, unlike callApi()'s, doesn't ask for JSON.
    $this->get('api/whoami')
        ->assertUnauthorized()
        ->assertExactJson(['message' => 'Unauthenticated.']);
});

it('lets an extension render its own 401', function () {
    $this->bootingWith(fn () => app(ExceptionHandler::class)->renderable(
        fn (AuthenticationException $e) => response()->json(['errors' => [['status' => '401']]], 401),
    ));
    Route::middleware(['api', 'larasell.api'])->get('api/whoami', fn () => 'never');

    callApi(null)->assertUnauthorized()->assertExactJson(['errors' => [['status' => '401']]]);
});

it('only authenticates API tokens through the larasell.api middleware', function () {
    // Its guard is an implementation detail: on its own it never has a user.
    Route::middleware(['api', 'auth:larasell-api-token'])->get('api/guard-only', fn () => 'reached');

    $this->getJson('api/guard-only', ['Authorization' => 'Bearer '.createApiToken()])->assertUnauthorized();
});

/**
 * Reboots with the API tokens module turned off.
 */
function disableApiTokens(): void
{
    test()->bootingWith(fn () => config(['larasell.admin.api_tokens.enabled' => false]));
}

it('leaves out the API tokens settings pages when disabled', function () {
    disableApiTokens();
    $this->actingAs($this->ada, 'larasell-admin');

    expect(Route::has('larasell.admin.settings.api-tokens.index'))->toBeFalse();

    // The routes aren't registered, so there are no route names to use.
    $this->get('/admin/settings/api-tokens')->assertNotFound(); // @phpstan-ignore larasell.preferRouteHelper
    $this->get('/admin/settings/api-tokens/create')->assertNotFound(); // @phpstan-ignore larasell.preferRouteHelper
    $this->post('/admin/settings/api-tokens', ['name' => 'x', 'expires_in' => '3_months'])->assertNotFound(); // @phpstan-ignore larasell.preferRouteHelper

    expect(ApiToken::query()->count())->toBe(0);
});

it('leaves out the API tokens navigation item when disabled', function () {
    disableApiTokens();
    $this->actingAs($this->ada, 'larasell-admin');

    $this->get(route('larasell.admin.home'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('navigation', fn ($items) => collect(collect($items)->firstWhere('label', 'Settings')['children'])->pluck('label')->all() === ['Admins']));
});

it('rejects every token on larasell.api routes when disabled, rather than failing', function () {
    disableApiTokens();
    // What an extension's API does, registered again since rebooting dropped the routes.
    Route::middleware(['api', 'larasell.api'])->get('api/whoami', fn () => 'reached');
    // A token issued while the module was on.
    ApiToken::query()->create(['name' => 'ERP sync', 'token_hash' => hash('sha256', 'larasell_issued-before'), 'expires_at' => now()->addMonth()]);

    callApi('larasell_issued-before')->assertUnauthorized();
});

it('keeps API tokens out of reach for guests', function () {
    $this->get(route('larasell.admin.settings.api-tokens.index'))->assertRedirect(route('larasell.admin.login'));
    $this->post(route('larasell.admin.settings.api-tokens.store'), ['name' => 'x', 'expires_in' => '3_months'])->assertRedirect(route('larasell.admin.login'));

    expect(ApiToken::query()->count())->toBe(0);
});
