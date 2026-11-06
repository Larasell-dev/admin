<?php

namespace Larasell\Admin\Product\Events;

use Illuminate\Queue\SerializesModels;
use Larasell\Admin\Platform\Sections\SectionContext;
use Larasell\Admin\Platform\Sections\SectionRequest;
use Larasell\Admin\Platform\Sections\SectionRequests;
use Larasell\Larasell\Models\Product;

/**
 * The product page saved a product, created or edited. Sections of the page listen to it to
 * save their own fields, which their SectionRequest validated with the product's:
 * `$event->section(ProductNotesRequest::class)->payload()`.
 *
 * It's dispatched inside the save's database transaction, once the product is written:
 *
 * - A listener that runs right away is atomic with the product. When it throws, nothing is
 *   saved. Validate in the section's request; throw a ValidationException for
 *   "sections.{key}.{field}" only for what can't be known before saving.
 * - A listener that must not hold up or undo the save, e.g. one that calls another service,
 *   implements ShouldQueue with `public bool $afterCommit = true`. It runs only once the
 *   product is saved, and failing doesn't change it.
 *
 * @public
 */
final class ProductSaved
{
    use SerializesModels;

    public function __construct(
        public readonly Product $product,
        /** Whether the product was created, so it had no ID while the page was shown. */
        public readonly bool $created,
        public readonly SectionContext $context,
        private readonly SectionRequests $sections,
    ) {}

    /**
     * A section's validated request, by its class: its validated() input and its payload().
     *
     * @template T of SectionRequest
     *
     * @param  class-string<T>  $request
     * @return T
     */
    public function section(string $request): SectionRequest
    {
        return $this->sections->get($request);
    }
}
