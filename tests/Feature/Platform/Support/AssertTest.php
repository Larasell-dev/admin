<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Exceptions;
use Larasell\Admin\Platform\Support\Assert;
use Larasell\Chronicle\Exceptions\StructuredLogicException;

function brokenPromise(string $code = 'larasell-admin.test.broken'): StructuredLogicException
{
    return new StructuredLogicException(errorCode: $code, message: 'A promise was broken.');
}

it('passes without building the exception', function () {
    expect(Assert::that(true, fn () => throw new LogicException('Built although the check passed.')))->toBeTrue();
});

it('throws the exception in the testing environment, so tests see the bug', function () {
    expect(fn () => Assert::that(false, brokenPromise(...)))
        ->toThrow(StructuredLogicException::class, 'A promise was broken.');
});

it('throws in debug mode, whatever the environment', function () {
    $this->app['env'] = 'production';
    config(['app.debug' => true]);

    expect(fn () => Assert::that(false, brokenPromise()))->toThrow(StructuredLogicException::class);
});

it('reports a failed check once per error code in production, and lets the caller fall back', function () {
    $this->app['env'] = 'production';
    config(['app.debug' => false]);
    Exceptions::fake();

    expect(Assert::that(false, brokenPromise()))->toBeFalse()
        ->and(Assert::that(false, brokenPromise()))->toBeFalse()
        ->and(Assert::that(false, brokenPromise('larasell-admin.test.other')))->toBeFalse();

    Exceptions::assertReportedCount(2);
    Exceptions::assertReported(fn (StructuredLogicException $exception) => $exception->errorCode() === 'larasell-admin.test.other');
});
