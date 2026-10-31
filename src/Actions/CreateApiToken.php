<?php

namespace Larasell\Admin\Actions;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Larasell\Admin\Enums\ApiTokenExpiry;
use Larasell\Admin\Models\Admin;
use Larasell\Admin\Models\ApiToken;

/**
 * @internal Tokens are created in the admin panel's settings.
 */
class CreateApiToken
{
    /**
     * Tells our tokens apart, e.g. for secret scanners.
     */
    private const string PREFIX = 'larasell_';

    /**
     * Creates an API token valid until the end of the day its period ends and returns its plaintext
     * token, the only time it is available: only a hash of it is stored.
     *
     * @param  array<string, mixed>  $input
     * @return array{ApiToken, string}
     *
     * @throws ValidationException
     */
    public function handle(array $input, ?Admin $createdBy): array
    {
        $validated = Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'expires_in' => ['required', Rule::enum(ApiTokenExpiry::class)],
        ])->validate();

        $entropy = Str::random(40);
        $secret = self::PREFIX.$entropy.hash('crc32b', $entropy);

        $apiToken = ApiToken::query()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'created_by' => $createdBy?->getKey(),
            // Only the hash is stored; the plaintext token exists only in this response.
            'token_hash' => hash('sha256', $secret),
            'expires_at' => ApiTokenExpiry::from($validated['expires_in'])->expiresAt(),
        ]);

        return [$apiToken, $secret];
    }
}
