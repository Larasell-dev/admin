<?php

return [

    /*
     * The disk exports of the audit log are written to until they are downloaded. Keep it private:
     * the files hold who did what, e.g. admins' email addresses. Exports are deleted 24 hours after
     * they were asked for, when larasell:prune-audit-log runs.
     */
    'export' => [
        'disk' => env('LARASELL_ADMIN_AUDIT_EXPORT_DISK', 'local'),
    ],

];
