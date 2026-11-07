<?php

namespace Larasell\Admin\SalesChannel\Actions;

/**
 * What an admin can change on a sales channel. Its key and type stay as they were created.
 */
final readonly class UpdateSalesChannelPayload
{
    /**
     * @param  list<string>  $locales  Tags from larasell.locale.supported.
     * @param  list<string>  $currencies  Codes of enabled currencies.
     */
    public function __construct(
        public string $name,
        public array $locales,
        public array $currencies,
        public string $defaultLocale,
        public string $defaultCurrency,
        public bool $active,
    ) {}
}
