<?php

namespace Larasell\Admin\SalesChannel\Data;

use Larasell\Larasell\Enums\Currency;
use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\SalesChannels\Models\SalesChannel;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class SalesChannelData extends Data
{
    public function __construct(
        public int $id,
        public string $key,
        public string $name,
        public string $type,
        /** @var string[] */
        public array $locales,
        /** @var string[] */
        public array $currencies,
        public string $defaultLocale,
        public string $defaultCurrency,
        public bool $active,
    ) {}

    public static function fromModel(SalesChannel $channel): self
    {
        return new self(
            id: $channel->id,
            key: $channel->key,
            name: $channel->name,
            type: $channel->type,
            locales: array_map(fn (Locale $locale): string => $locale->value, $channel->locales()),
            currencies: array_map(fn (Currency $currency): string => $currency->value, $channel->currencies()),
            defaultLocale: $channel->default_locale->value,
            defaultCurrency: $channel->default_currency->value,
            active: $channel->active,
        );
    }
}
