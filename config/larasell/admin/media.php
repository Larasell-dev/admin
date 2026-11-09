<?php

return [

    /*
     * Whether the admin panel manages media. Set it to false to remove the
     * media library's pages and its navigation item, for example when media
     * is managed elsewhere.
     */
    'enabled' => env('LARASELL_ADMIN_MEDIA_ENABLED', true),

    /*
     * The queue the media library's placeholders are made on. The same as the
     * core's thumbnails by default, so one worker does both; a worker on
     * another server needs disks it shares with the app, e.g. S3.
     */
    'queue' => env('LARASELL_MEDIA_QUEUE', 'media'),

];
