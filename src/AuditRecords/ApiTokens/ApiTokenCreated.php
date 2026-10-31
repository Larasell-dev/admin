<?php

namespace Larasell\Admin\AuditRecords\ApiTokens;

use Larasell\Admin\Audit\AuditCategory;
use Larasell\Admin\Audit\AuditRecord;
use Larasell\Admin\Audit\Subject;
use Larasell\Admin\Models\ApiToken;

/**
 * An admin created an API token. Never with the token or its hash.
 *
 * @internal Recorded by CreateApiToken. Its event name, api_token.created, is stable.
 */
class ApiTokenCreated extends AuditRecord
{
    public function __construct(private readonly ApiToken $apiToken) {}

    public function event(): string
    {
        return 'api_token.created';
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
            'description' => $this->apiToken->description,
            'expires_at' => $this->apiToken->expires_at->copy()->utc()->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
