<?php

namespace Larasell\Admin\ContentLocale;

use Attribute;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Container\ContextualAttribute;
use Larasell\Chronicle\Facades\Chronicle;
use Larasell\Larasell\Enums\Locale;

/**
 * Injects the locale the current request edits content in, like Laravel's #[CurrentUser]:
 * `public function edit(Product $product, #[CurrentContentLocale] Locale $locale)`.
 * It asks the ContentLocaleResolver bound in the container.
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
final class CurrentContentLocale implements ContextualAttribute
{
    public static function resolve(self $attribute, Container $container): Locale
    {
        $locale = $container->make(ContentLocaleResolver::class)->resolve($container->make('request'));

        // Says which translation a request read or wrote, e.g. when a name changed in the wrong language.
        Chronicle::add('content_locale.current', $locale->value);

        return $locale;
    }
}
