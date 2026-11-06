<?php

namespace Larasell\Admin\Platform\Sections;

/**
 * The pages that modules and plugins can add sections to. The frontend registers a section's
 * component under the same value: sections.register("product", ...).
 *
 * @public
 */
enum SectionPage: string
{
    /** The product's create and edit pages; sections save with ProductSaved. */
    case Product = 'product';
}
