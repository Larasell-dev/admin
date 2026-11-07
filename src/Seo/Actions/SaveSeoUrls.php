<?php

namespace Larasell\Admin\Seo\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Larasell\Admin\Category\Events\CategorySaved;
use Larasell\Admin\Platform\Sections\SectionContext;
use Larasell\Admin\Product\Events\ProductSaved;
use Larasell\Admin\Seo\Http\Requests\SeoUrlsRequest;
use Larasell\Larasell\Seo\Exceptions\InvalidSeoPath;
use Larasell\Larasell\Seo\SeoUrlManager;

/**
 * Saves a product's or category's URLs with it: a custom path where the admin typed one, the
 * automatic one where the field was emptied. Channels the page didn't show keep theirs.
 */
class SaveSeoUrls
{
    public function __construct(private SeoUrlManager $urls) {}

    /**
     * @throws ValidationException when a path is malformed, reserved or used by another page
     */
    public function product(ProductSaved $event): void
    {
        $this->save($event->product, $event->context, $event->section(SeoUrlsRequest::class));
    }

    /**
     * @throws ValidationException when a path is malformed, reserved or used by another page
     */
    public function category(CategorySaved $event): void
    {
        $this->save($event->category, $event->context, $event->section(SeoUrlsRequest::class));
    }

    /**
     * @throws ValidationException
     */
    private function save(Model $resource, SectionContext $page, SeoUrlsRequest $section): void
    {
        $payload = $section->payload();

        if ($payload === null) {
            return;
        }

        foreach ($this->urls->contexts() as $context) {
            $channelId = $context->channelId->value;

            if ($context->locale !== $page->locale || ! array_key_exists($channelId, $payload->paths)) {
                continue;
            }

            $path = $payload->paths[$channelId];

            try {
                $path === null
                    ? $this->urls->resetToAutomatic($resource, $context)
                    : $this->urls->setCustom($resource, $context, $path);
            } catch (InvalidSeoPath $exception) {
                throw ValidationException::withMessages([
                    'sections.'.SeoUrlsRequest::SECTION.".urls.{$channelId}.path" => match ($exception->reason) {
                        InvalidSeoPath::TAKEN => __('Another page already uses this URL.'),
                        InvalidSeoPath::RESERVED => __('This URL is reserved for other pages of the store.'),
                        default => __('Use letters, numbers, dashes, dots and slashes, without spaces.'),
                    },
                ]);
            }
        }
    }
}
