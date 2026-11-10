<?php

namespace Larasell\Admin\Customer\Data;

use Illuminate\Support\Facades\App;
use Larasell\Larasell\Enums\Country;
use Larasell\Larasell\Enums\Currency;
use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\Settings\CurrencySettings;
use Larasell\Larasell\Settings\LocaleSettings;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A language or currency a customer can prefer, named in the interface's language. */
#[TypeScript]
class CustomerOptionData extends Data
{
    public function __construct(public string $value, public string $label) {}

    /** @return array<int, self> The shop's supported languages. */
    public static function locales(): array
    {
        return array_map(
            fn (Locale $locale): self => new self($locale->value, \Locale::getDisplayName($locale->value, App::currentLocale()) ?: $locale->value),
            app(LocaleSettings::class)->supported(),
        );
    }

    /** @return array<int, self> Every country, named in the interface's language and sorted by name. */
    public static function countries(): array
    {
        $countries = array_map(fn (Country $country): self => new self($country->value, $country->label()), Country::cases());
        usort($countries, fn (self $a, self $b): int => strcoll($a->label, $b->label));

        return $countries;
    }

    /** @return array<int, self> The shop's enabled currencies, e.g. "Euro (EUR)". */
    public static function currencies(): array
    {
        $names = \ResourceBundle::create(App::currentLocale(), 'ICUDATA-curr')?->get('Currencies');

        return array_map(function (Currency $currency) use ($names): self {
            $name = $names?->get($currency->value)?->get(1);

            return new self($currency->value, is_string($name) && $name !== '' ? "{$name} ({$currency->value})" : $currency->value);
        }, app(CurrencySettings::class)->enabled());
    }
}
