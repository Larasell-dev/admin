<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Larasell\Admin\ApiToken\Models\ApiToken;
use Larasell\Admin\Platform\Audit\CurrentOrigin;
use Larasell\Admin\Platform\Auth\Models\Admin;
use Larasell\Chronicle\Exceptions\StructuredLogicException;
use Larasell\Larasell\Audit\Origin;

beforeEach(function () {
    $this->ada = Admin::create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'password' => 'password']);
    $describe = fn (#[CurrentOrigin] Origin $origin): array => [
        'actor_type' => $origin->actor->auditActorType(),
        'actor_id' => $origin->actor->auditActorId(),
        'source' => $origin->source->value,
    ];

    Route::middleware('web')->post('books/archive', $describe);
    Route::middleware(['api', 'larasell.api'])->post('api/books/archive', $describe);
});

it('names the signed-in admin on the panel\'s pages', function () {
    // Act & Assert
    $this->actingAs($this->ada, 'larasell-admin')->post('books/archive')
        ->assertOk()
        ->assertExactJson(['actor_type' => 'admin', 'actor_id' => (string) $this->ada->id, 'source' => 'web']);
});

it('names the API token on API requests, even with an admin signed in', function () {
    // Arrange
    $apiToken = ApiToken::query()->create(['name' => 'ERP sync', 'token_hash' => hash('sha256', 'larasell_erp'), 'expires_at' => now()->addMonth()]);

    // Act & Assert
    $this->actingAs($this->ada, 'larasell-admin')
        ->postJson('api/books/archive', headers: ['Authorization' => 'Bearer larasell_erp'])
        ->assertOk()
        ->assertExactJson(['actor_type' => 'api_token', 'actor_id' => (string) $apiToken->id, 'source' => 'api']);
});

it('fails when no one is signed in, instead of auditing a change as no one\'s', function () {
    // Act & Assert
    $this->withoutExceptionHandling();

    expect(fn () => $this->post('books/archive'))->toThrow(StructuredLogicException::class, 'no origin');
});
