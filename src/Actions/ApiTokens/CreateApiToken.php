<?php

namespace Larasell\Admin\Actions\ApiTokens;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Larasell\Admin\Audit\Auditor;
use Larasell\Admin\AuditRecords\ApiTokens\ApiTokenCreated;
use Larasell\Admin\Enums\ApiTokens\ApiTokenExpiry;
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

    public function __construct(private readonly Auditor $auditor) {}

    /**
     * Creates an API token valid until the end of the day its period ends and returns its plaintext
     * token, the only time it is available: only a hash of it is stored. No token is created if
     * its creation can't be audited.
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

        $apiToken = DB::transaction(function () use ($validated, $createdBy, $secret): ApiToken {
            $apiToken = ApiToken::query()->create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'created_by' => $createdBy?->getKey(),
                // Only the hash is stored; the plaintext token exists only in this response.
                'token_hash' => hash('sha256', $secret),
                'expires_at' => ApiTokenExpiry::from($validated['expires_in'])->expiresAt(),
            ]);

            $this->auditor->record(new ApiTokenCreated($apiToken));

            return $apiToken;
        });

        return [$apiToken, $secret];
    }
}
