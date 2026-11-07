<?php

namespace Larasell\Admin\Seo\Actions;

use Larasell\Admin\Seo\SeoUrlId;

/** Removing an old URL, with the text the admin typed to confirm it. */
final readonly class RemoveOldUrlPayload
{
    public function __construct(
        public SeoUrlId $url,
        public ?string $confirmation,
    ) {}
}
