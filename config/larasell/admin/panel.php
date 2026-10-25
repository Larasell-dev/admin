<?php

return [

    /*
     * The URL path the admin panel is served under, for example "admin" for
     * https://example.com/admin.
     */
    'path' => env('LARASELL_ADMIN_PATH', 'admin'),

    /*
     * The filesystem disk for files uploaded through the admin panel, such as
     * avatars. Uploads are stored publicly, so the disk must be able to serve
     * public URLs.
     */
    'disk' => env('LARASELL_ADMIN_DISK', 'public'),

    /*
     * The namespace your table classes live in. Table actions are run through
     * a URL that names the table by its path in this namespace, for example
     * App\Tables\ProductReviews becomes "product-reviews".
     */
    'tables' => [
        'namespace' => 'App\\Tables',
    ],

];
