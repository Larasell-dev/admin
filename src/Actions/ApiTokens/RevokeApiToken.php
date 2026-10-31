<?php

namespace Larasell\Admin\Actions\ApiTokens;

use Illuminate\Support\Facades\DB;
use Larasell\Admin\Audit\Auditor;
use Larasell\Admin\AuditRecords\ApiTokens\ApiTokenRevoked;
use Larasell\Admin\Models\ApiToken;

/**
 * @internal Tokens are revoked in the admin panel's settings.
 */
class RevokeApiToken
{
    public function __construct(private readonly Auditor $auditor) {}

    /**
     * Deletes the API token, so its next request is unauthenticated. The token stays if its
     * revocation can't be audited.
     */
    public function handle(ApiToken $apiToken): void
    {
        DB::transaction(function () use ($apiToken): void {
            $apiToken->delete();

            $this->auditor->record(new ApiTokenRevoked($apiToken));
        });
    }
}
