<?php

namespace Larasell\Admin\SalesChannel\Actions;

/**
 * A new sales channel. The key names it in code, e.g. SalesContext::for('b2b'), so it can't change
 * later. The type is a key registered with SalesChannelTypes, such as "storefront".
 */
final readonly class CreateSalesChannelPayload
{
    /**
     * @param  list<string>  $locales  Tags from larasell.locale.supported.
     * @param  list<string>  $currencies  Codes of enabled currencies.
     */
    public function __construct(
        public string $key,
        public string $name,
        public string $type,
        public array $locales,
        public array $currencies,
        public string $defaultLocale,
        public string $defaultCurrency,
        public bool $active,
    ) {}
}
