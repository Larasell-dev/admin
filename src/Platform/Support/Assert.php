<?php

namespace Larasell\Admin\Platform\Support;

use Closure;
use Larasell\Chronicle\Exceptions\StructuredException;
use Throwable;

/**
 * Checks the panel's own guarantees: something one part of the panel promises and another relies
 * on. A failed check is a bug, but one the panel can carry on from, so it doesn't take a page down
 * in production.
 *
 * Where assertions go: the side that promises something asserts it, and the side that relies on it
 * asserts it again. A pair is a contract; one side alone is only a comment. Both sides pass an
 * exception with the same error code, so a broken promise is reported once, not once per side.
 *
 * What a failed check does:
 * - In debug mode, and in the local and testing environments, it throws the exception, so tests
 *   and developers see it at once.
 * - Everywhere else it reports the exception through report(), once per error code and process,
 *   and returns false, so the caller falls back to something safe.
 *
 * Use it only where carrying on keeps data correct. Where it can't, e.g. while saving, throw.
 * Not PHP's assert(): production sets zend.assertions to -1, which compiles those out.
 *
 * @internal The panel's own; plugins throw their own exceptions.
 */
final class Assert
{
    /** @var array<string, true> The error codes this process already reported. */
    private array $reported = [];

    /**
     * Returns the condition, so outside debug mode the caller can fall back when it is false.
     *
     * @param  (Closure(): (StructuredException&Throwable))|(StructuredException&Throwable)  $exception  Built only when the check fails
     */
    public static function that(bool $condition, Closure|StructuredException $exception): bool
    {
        if (! $condition) {
            app(self::class)->fail($exception instanceof Closure ? $exception() : $exception);
        }

        return $condition;
    }

    private function fail(StructuredException&Throwable $exception): void
    {
        if (app()->hasDebugModeEnabled() || app()->environment('local', 'testing')) {
            throw $exception;
        }

        $code = $exception->errorCode() ?? $exception::class;

        if (! isset($this->reported[$code])) {
            $this->reported[$code] = true;
            report($exception);
        }
    }
}
