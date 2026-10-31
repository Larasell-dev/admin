<?php

namespace Larasell\Admin\Http\Middleware\ApiTokens;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Larasell\Admin\Models\ApiToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Behind the larasell.api middleware alias, the public way to require an API token from the
 * admin panel. A request sends the token as `Authorization: Bearer <token>`, an opaque string.
 * With a valid token, $request->user() is its ApiToken. Without one, the middleware throws an
 * AuthenticationException: an extension can render it as its API's own 401, and otherwise the
 * response is a JSON 401, never a redirect to a login page.
 *
 * It accepts no session, and only tokens issued in the admin panel, so tokens or sessions the
 * app has for its own users don't open these routes.
 *
 * @internal Use the larasell.api alias; this class and its guard may change behind it.
 */
class AuthenticateApiToken
{
    private const string GUARD = 'larasell-api-token';

    /**
     * Failed attempts one address may make per minute, so wrong tokens can't cost unlimited lookups.
     */
    private const int FAILED_ATTEMPTS_PER_MINUTE_MAX = 30;

    /**
     * Registers the larasell.api alias, its guard, and the fallback 401.
     *
     * @internal
     */
    public static function register(): void
    {
        config(['auth.guards.'.self::GUARD => ['driver' => self::GUARD]]);

        // The guard only knows the token this middleware sets, so auth:<guard> on its own never
        // authenticates. It exists so Auth::user() is the token on these routes.
        Auth::viaRequest(self::GUARD, fn (): null => null);

        app(Router::class)->aliasMiddleware('larasell.api', self::class);

        // Once every provider has booted, so an extension's own renderer for the 401 comes first.
        app()->booted(fn () => app(ExceptionHandler::class)->renderable(
            fn (AuthenticationException $e): ?JsonResponse => in_array(self::GUARD, $e->guards(), true)
                ? new JsonResponse(['message' => $e->getMessage()], 401)
                : null,
        ));
    }

    public function handle(Request $request, Closure $next): Response
    {
        $failedAttempts = 'larasell-api:failed:'.$request->ip();

        // Before the lookup, so a blocked address costs no query, even with a valid token.
        if (RateLimiter::tooManyAttempts($failedAttempts, self::FAILED_ATTEMPTS_PER_MINUTE_MAX)) {
            throw new ThrottleRequestsException(headers: ['Retry-After' => RateLimiter::availableIn($failedAttempts)]);
        }

        $apiToken = $this->resolve($request);

        if ($apiToken === null) {
            RateLimiter::hit($failedAttempts, 60);

            throw new AuthenticationException(guards: [self::GUARD]);
        }

        Auth::guard(self::GUARD)->setUser($apiToken);
        Auth::shouldUse(self::GUARD);

        return $next($request);
    }

    private function resolve(Request $request): ?ApiToken
    {
        $plainTextToken = $request->bearerToken();

        if ($plainTextToken === null) {
            return null;
        }

        // A bare "Bearer " header.
        if ($plainTextToken === '') {
            return null;
        }

        // The token is 40 random characters, so a plain SHA-256 is enough and lets the unique
        // index find it; a slow password hash would only cost every request time.
        $apiToken = ApiToken::query()->where('token_hash', hash('sha256', $plainTextToken))->first();

        if ($apiToken === null) {
            return null;
        }

        if ($apiToken->isExpired()) {
            return null;
        }

        // At most once a minute: a polling integration shouldn't turn every read into a write.
        if ($apiToken->last_used_at === null || $apiToken->last_used_at->lte(now()->subMinute())) {
            $apiToken->forceFill(['last_used_at' => now()])->save();
        }

        return $apiToken;
    }
}
