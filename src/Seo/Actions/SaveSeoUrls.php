<?php

namespace Larasell\Admin\Seo\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Larasell\Admin\Product\Events\ProductSaved;
use Larasell\Admin\Seo\Http\Requests\SeoUrlsRequest;
use Larasell\Larasell\Catalog\Events\CategoryCreated;
use Larasell\Larasell\Catalog\Events\CategoryUpdated;
use Larasell\Larasell\Catalog\Models\Category;
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
        $this->save($event->product, $event->section(SeoUrlsRequest::class)->payload());
    }

    /**
     * Runs in the core action's transaction, so a refused path saves nothing of the category.
     *
     * @throws ValidationException when a path is malformed, reserved or used by another page
     */
    public function category(CategoryCreated|CategoryUpdated $event): void
    {
        $payload = $event->attachments->get(SeoUrlsPayload::class);

        if ($payload !== null) {
            $this->save(Category::query()->findOrFail($event->categoryId->value), $payload);
        }
    }

    /**
     * @throws ValidationException
     */
    private function save(Model $resource, ?SeoUrlsPayload $payload): void
    {
        if ($payload === null) {
            return;
        }

        foreach ($this->urls->contexts() as $context) {
            $channelId = $context->channelId->value;

            if ($context->locale !== $payload->locale || ! array_key_exists($channelId, $payload->paths)) {
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
