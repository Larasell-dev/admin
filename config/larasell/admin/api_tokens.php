<?php

return [

    /*
     * Whether admins can issue API tokens for extension APIs. Set it to false to remove the
     * API tokens settings pages and their navigation item. Routes behind the larasell.api
     * middleware then answer every request with a 401, including tokens issued before.
     */
    'enabled' => env('LARASELL_ADMIN_API_TOKENS_ENABLED', true),

];
