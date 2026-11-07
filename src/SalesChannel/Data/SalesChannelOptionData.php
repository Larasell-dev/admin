<?php

namespace Larasell\Admin\SalesChannel\Data;

use Illuminate\Support\Facades\App;
use Larasell\Larasell\Enums\Currency;
use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\Settings\CurrencySettings;
use Larasell\Larasell\Settings\LocaleSettings;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A locale or currency a sales channel can sell in.
 */
#[TypeScript]
class SalesChannelOptionData extends Data
{
    public function __construct(
        public string $value,
        public string $label,
    ) {}

    /**
     * The store's supported locales, named in the interface's language.
     *
     * @return list<self>
     */
    public static function locales(): array
    {
        return array_map(
            fn (Locale $locale): self => new self($locale->value, \Locale::getDisplayName($locale->value, App::currentLocale()) ?: $locale->value),
            app(LocaleSettings::class)->supported(),
        );
    }

    /**
     * The currencies enabled in the settings, named in the interface's language, e.g. "Euro"; the
     * page shows the code next to it. The names come from ICU, like the locales'; a code ICU
     * doesn't know is its own name.
     *
     * @return list<self>
     */
    public static function currencies(): array
    {
        $names = \ResourceBundle::create(App::currentLocale(), 'ICUDATA-curr')?->get('Currencies');

        return array_map(
            function (Currency $currency) use ($names): self {
                $name = $names?->get($currency->value)?->get(1);

                return new self($currency->value, is_string($name) && $name !== '' ? $name : $currency->value);
            },
            app(CurrencySettings::class)->enabled(),
        );
    }
}
