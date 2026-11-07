<?php

namespace Larasell\Admin\SalesChannel\Data;

use Larasell\Larasell\Storefront\Models\EntryPoint;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A domain of a storefront channel, as its page lists it. */
#[TypeScript]
class EntryPointData extends Data
{
    public function __construct(
        public int $id,
        public string $url,
        /** The URL without its scheme, e.g. shop.example/de, which the admin types to delete it. */
        public string $domain,
        public string $locale,
        /** The currency visits start in, or null for the channel's default. */
        public ?string $defaultCurrency,
        /** Whether links in its language use this domain. */
        public bool $primary,
        public string $destroyUrl,
    ) {}

    /** The host, port and prefix of a domain, e.g. shop.example/de. */
    public static function domain(EntryPoint $entryPoint): string
    {
        return (string) preg_replace('#^[a-z][a-z0-9+.-]*://#i', '', $entryPoint->toEntryPoint()->url());
    }

    public static function fromModel(EntryPoint $entryPoint): self
    {
        return new self(
            id: $entryPoint->id,
            url: $entryPoint->toEntryPoint()->url(),
            domain: self::domain($entryPoint),
            locale: $entryPoint->locale->value,
            defaultCurrency: $entryPoint->default_currency?->value,
            primary: $entryPoint->is_primary === true,
            destroyUrl: route('larasell.admin.sales-channels.entry-points.destroy', [$entryPoint->sales_channel_id, $entryPoint->id]),
        );
    }
}
