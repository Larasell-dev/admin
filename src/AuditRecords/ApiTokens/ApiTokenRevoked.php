<?php

namespace Larasell\Admin\AuditRecords\ApiTokens;

use Larasell\Admin\Audit\AuditCategory;
use Larasell\Admin\Audit\AuditRecord;
use Larasell\Admin\Audit\Subject;
use Larasell\Admin\Models\ApiToken;

/**
 * An admin revoked an API token. Revoking deletes the token, so this keeps its name.
 *
 * @internal Recorded by RevokeApiToken. Its event name, api_token.revoked, is stable.
 */
class ApiTokenRevoked extends AuditRecord
{
    public function __construct(private readonly ApiToken $apiToken) {}

    public function event(): string
    {
        return 'api_token.revoked';
    }

    public function category(): AuditCategory
    {
        return AuditCategory::Security;
    }

    public function subject(): Subject
    {
        return new Subject('api_token', $this->apiToken->getKey());
    }

    public function properties(): array
    {
        return [
            'name' => $this->apiToken->name,
            'expires_at' => $this->apiToken->expires_at->copy()->utc()->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
