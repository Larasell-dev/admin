<?php

namespace Larasell\Admin\Exceptions;

use Larasell\Chronicle\HasStructuredError;
use Larasell\Chronicle\StructuredError;
use RuntimeException;

/**
 * Why Auditor::record() refused or failed to store a record.
 *
 * @public Catch it; only the audit log creates it, so its named constructors are internal.
 */
class AuditFailed extends RuntimeException implements StructuredError
{
    use HasStructuredError;

    /**
     * @internal
     */
    public static function outsideTransaction(string $event): static
    {
        return static::structured(
            code: 'larasell-admin.audit.outside-transaction',
            message: "Audit record [{$event}] was recorded outside a database transaction.",
            why: 'Outside a transaction, the change could be stored without its audit record, or the record without its change.',
            fix: 'Make the change and record it inside the same DB::transaction().',
        );
    }

    /**
     * @internal
     */
    public static function missingActor(string $event, string $source): static
    {
        return static::structured(
            code: 'larasell-admin.audit.missing-actor',
            message: "Audit record [{$event}] has no actor.",
            why: "No admin or API token is signed in, or the record comes from a {$source} source, which must name its actor.",
            fix: 'Pass the actor to Auditor::record(), e.g. new SystemActor(\'inventory-reconciliation\') in a job or command, or new UnknownActor(\'why\') if it truly can\'t be known.',
        );
    }

    /**
     * @internal
     */
    public static function credentialProperty(string $event, string $property): static
    {
        return static::structured(
            code: 'larasell-admin.audit.credential-property',
            message: "Audit record [{$event}] has property [{$property}], which looks like a credential.",
            why: 'Audit records are kept for a long time and read by other people, so they never store passwords, tokens, secrets or their hashes.',
            fix: "Leave [{$property}] out of properties(). The record's subject already says which credential it was about.",
        );
    }

    /**
     * @internal
     */
    public static function invalidProperty(string $event, string $property, string $type): static
    {
        return static::structured(
            code: 'larasell-admin.audit.invalid-property',
            message: "Audit record [{$event}] has property [{$property}] of type [{$type}].",
            why: 'Properties are stored as JSON, and an object would be stored however it happens to serialize, possibly with more data than intended.',
            fix: "Return a string, number, boolean, null or array for [{$property}], e.g. a date formatted in UTC.",
        );
    }

    /**
     * @internal
     *
     * @param  class-string  $actor
     */
    public static function unnamedActor(string $actor, string $field): static
    {
        return static::structured(
            code: 'larasell-admin.audit.unnamed-actor',
            message: "{$actor} was created without a {$field}.",
            why: "Without a {$field}, the audit log can't tell who or what made the change.",
            fix: "Pass a {$field} that explains it to someone reading the audit log later.",
        );
    }
}
