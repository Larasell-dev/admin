<?php

namespace Larasell\Admin\Platform\Sections;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * The forms that modules and plugins can add sections to, e.g. the product form on the product's
 * create and edit pages. The frontend registers a section's component under the same value:
 * sections.register(SectionForm.Product, ...). Its values are generated into the frontend's
 * types with `composer types`.
 *
 * @public
 */
#[TypeScript]
enum SectionForm: string
{
    /**
     * The product form, on the product's create and edit pages. Its own sections are the
     * ProductSection cases; sections save with ProductSaved.
     */
    case Product = 'product';

    /**
     * The category form, on the category's create and show pages. Its own sections are the
     * CategorySection cases; sections save with the core's CategoryCreated and CategoryUpdated, which carry their payloads as Attachments.
     */
    case Category = 'category';
}
