<?php

return [

    /*
     * How many days to keep audit records, counted from when the action happened. Null keeps them
     * forever, the default, since how long to keep them is up to the shop's obligations.
     *
     * Records older than their period are deleted when `php artisan larasell:prune-audit-log` runs.
     * Nothing deletes them otherwise, so schedule the command, e.g. daily, in routes/console.php:
     *
     *     Schedule::command('larasell:prune-audit-log')->daily();
     *
     * Each category can have its own period, e.g. 'security' => 730. A category left out keeps its
     * records for default_days; one set to null keeps them forever. The categories are security,
     * business_critical and audit_access.
     *
     * Whoever can deploy this file can change these settings. Each run of the command records the
     * periods it applied as an audit_log.pruned record, so a change shows in the audit log from the
     * first run it applies to.
     */
    'retention' => [
        'default_days' => env('LARASELL_ADMIN_AUDIT_RETENTION_DAYS'),
        'categories' => [
            // 'security' => 730,
        ],
    ],

    /*
     * While true, larasell:prune-audit-log deletes no records, e.g. while a legal hold requires
     * keeping them. It still records each run, so the log shows the hold was in place.
     */
    'legal_hold' => env('LARASELL_ADMIN_AUDIT_LEGAL_HOLD', false),

];
