<?php

namespace Larasell\Admin\Data\ViewModel\ContentLocale;

use Illuminate\Support\Facades\App;
use Larasell\Admin\ContentLocale\AvailableLocales;
use Larasell\Larasell\Enums\Locale;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * The locale a page edits content in, and the others to switch to.
 */
#[TypeScript]
class ContentLocaleData extends Data
{
    public function __construct(
        public string $current,
        /** @var array<int, ContentLocaleOptionData> The page leaves out the select while there is only one. */
        public array $options,
        public string $switchUrl,
    ) {}

    public static function of(Locale $current): self
    {
        return new self(
            current: $current->value,
            options: array_map(
                // Named in the interface's language, which the content locale doesn't change.
                fn (Locale $locale): ContentLocaleOptionData => new ContentLocaleOptionData(
                    $locale->value,
                    \Locale::getDisplayName($locale->value, App::currentLocale()) ?: $locale->value,
                ),
                app(AvailableLocales::class)->all(),
            ),
            switchUrl: route('larasell.admin.content-locale.update'),
        );
    }
}
