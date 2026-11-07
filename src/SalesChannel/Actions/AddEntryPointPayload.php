<?php

namespace Larasell\Admin\SalesChannel\Actions;

/**
 * A domain an admin adds to a storefront channel: the URL customers open, the language it serves,
 * and optionally the currency visits start in. Visitors can still switch to any of the channel's.
 */
final readonly class AddEntryPointPayload
{
    /**
     * @param  string  $url  A scheme and host with an optional path prefix, e.g. https://shop.example/de.
     * @param  string  $locale  One of the channel's locales.
     * @param  string|null  $defaultCurrency  One of the channel's currencies, or null for the channel's default.
     * @param  bool  $primary  Whether links in this language use it; the first one of them always is.
     */
    public function __construct(
        public string $url,
        public string $locale,
        public ?string $defaultCurrency,
        public bool $primary,
    ) {}
}
