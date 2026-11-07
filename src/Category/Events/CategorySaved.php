<?php

namespace Larasell\Admin\Category\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Larasell\Admin\Category\Exceptions\CategoryNotSavedAtomically;
use Larasell\Admin\Platform\Sections\SectionContext;
use Larasell\Admin\Platform\Sections\SectionRequest;
use Larasell\Admin\Platform\Sections\SectionRequests;
use Larasell\Admin\Platform\Support\Assert;
use Larasell\Larasell\Catalog\Models\Category;

/**
 * The category page saved a category, created or edited. Sections of the page listen to it to
 * save their own fields, which their SectionRequest validated with the category's:
 * `$event->section(CategoryNotesRequest::class)->payload()`.
 *
 * It's dispatched inside the save's database transaction, once the category is written:
 *
 * - A listener that runs right away is atomic with the category. When it throws, nothing is
 *   saved. Validate in the section's request; throw a ValidationException for
 *   "sections.{key}.{field}" only for what can't be known before saving.
 * - A listener that must not hold up or undo the save, e.g. one that calls another service,
 *   implements ShouldQueue with `public bool $afterCommit = true`. It runs only once the
 *   category is saved, and failing doesn't change it.
 *
 * @public
 */
final class CategorySaved
{
    use SerializesModels;

    public function __construct(
        public readonly Category $category,
        /** Whether the category was created, so it had no ID while the page was shown. */
        public readonly bool $created,
        public readonly SectionContext $context,
        private readonly SectionRequests $sections,
    ) {
        // The listeners' half of the contract CategoryController asserts before dispatching: a
        // sync listener that throws rolls the category back.
        Assert::that(DB::transactionLevel() > 0, CategoryNotSavedAtomically::outsideTransaction(...));
    }

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
