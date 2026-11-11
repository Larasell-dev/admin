<?php

namespace Larasell\Admin\Platform\Audit;

use Attribute;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Container\ContextualAttribute;
use Larasell\Larasell\Audit\Origin;

/**
 * Injects who makes the current request's changes, to pass to the core's audited actions:
 * `public function update(ProductRequest $request, #[CurrentOrigin] Origin $origin)`.
 * Fails when no admin or API token is signed in.
 *
 * @public
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
final class CurrentOrigin implements ContextualAttribute
{
    public static function resolve(self $attribute, Container $container): Origin
    {
        return $container->make(Origins::class)->current();
    }
}
