<?php

namespace Larasell\Admin\Seo\Actions;

use Illuminate\Validation\ValidationException;
use Larasell\Admin\Product\Events\ProductSaved;
use Larasell\Admin\Seo\Http\Requests\ProductUrlsRequest;
use Larasell\Larasell\Seo\InvalidSeoPath;
use Larasell\Larasell\Seo\SeoUrlManager;

/**
 * Saves the product's URLs with the product: a custom path where the admin typed one, the
 * automatic one where the field was emptied. Channels the page didn't show keep theirs.
 */
class SaveProductUrls
{
    public function __construct(private SeoUrlManager $urls) {}

    /**
     * @throws ValidationException when a path is malformed, reserved or used by another page
     */
    public function handle(ProductSaved $event): void
    {
        $payload = $event->section(ProductUrlsRequest::class)->payload();

        if ($payload === null) {
            return;
        }

        foreach ($this->urls->contexts() as $context) {
            $channelId = $context->channelId->value;

            if ($context->locale !== $event->context->locale || ! array_key_exists($channelId, $payload->paths)) {
                continue;
            }

            $path = $payload->paths[$channelId];

            try {
                $path === null
                    ? $this->urls->resetToAutomatic($event->product, $context)
                    : $this->urls->setCustom($event->product, $context, $path);
            } catch (InvalidSeoPath $exception) {
                throw ValidationException::withMessages([
                    'sections.'.ProductUrlsRequest::SECTION.".urls.{$channelId}.path" => match ($exception->reason) {
                        InvalidSeoPath::TAKEN => __('Another page already uses this URL.'),
                        InvalidSeoPath::RESERVED => __('This URL is reserved for other pages of the store.'),
                        default => __('Use letters, numbers, dashes, dots and slashes, without spaces.'),
                    },
                ]);
            }
        }
    }
}
