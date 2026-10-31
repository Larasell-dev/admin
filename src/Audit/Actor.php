<?php

namespace Larasell\Admin\Audit;

/**
 * Who did an audited action: an admin, an API token, or a SystemActor for jobs and commands.
 * Who it is, not how they signed in; the record's source says that.
 *
 * @public Implement it for anything else that acts, e.g. an integration.
 */
interface Actor
{
    /**
     * A stable name such as admin or api_token, rather than a class name.
     */
    public function auditActorType(): string;

    public function auditActorId(): string;
}
