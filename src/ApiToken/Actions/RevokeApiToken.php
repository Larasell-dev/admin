<?php

namespace Larasell\Admin\ApiToken\Actions;

use Illuminate\Support\Facades\DB;
use Larasell\Admin\ApiToken\AuditRecords\ApiTokenRevoked;
use Larasell\Admin\ApiToken\Models\ApiToken;
use Larasell\Larasell\Audit\Auditor;
use Larasell\Larasell\Audit\Origin;

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
    public function handle(ApiToken $apiToken, Origin $origin): void
    {
        DB::transaction(function () use ($origin, $apiToken): void {
            $apiToken->delete();

            $this->auditor->record(new ApiTokenRevoked($apiToken), $origin);
        });
    }
}
