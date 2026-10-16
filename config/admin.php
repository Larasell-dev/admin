<?php

return [

    'path' => env('LARASELL_ADMIN_PATH', 'admin'),

    /*
     * The filesystem disk for files uploaded through the admin panel, such as
     * avatars. Uploads are stored publicly, so the disk must be able to serve
     * public URLs.
     */
    'disk' => env('LARASELL_ADMIN_DISK', 'public'),

];
