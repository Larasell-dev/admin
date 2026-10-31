<?php

namespace Larasell\Admin\Actions\ApiTokens;

use Larasell\Admin\Models\ApiToken;

/**
 * @internal Tokens are revoked in the admin panel's settings.
 */
class RevokeApiToken
{
    /**
     * Deletes the API token, so its next request is unauthenticated.
     */
    public function handle(ApiToken $apiToken): void
    {
        $apiToken->delete();
    }
}
