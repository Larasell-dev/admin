<?php

namespace Larasell\Admin\Seo\Actions;

use Larasell\Admin\Seo\SeoUrlId;

/** Making an old URL its page's current one again. */
final readonly class RestoreOldUrlPayload
{
    public function __construct(public SeoUrlId $url) {}
}
