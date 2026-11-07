<?php

namespace Larasell\Admin\Category\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * The category form's own sections, in the order the form shows them. Sections that modules and
 * plugins add go before or after one of them: Section::add(...)->after(CategorySection::General).
 *
 * @public
 */
#[TypeScript]
enum CategorySection: string
{
    /** The name and visibility. */
    case General = 'general';

    /** The products listed in the category. */
    case Products = 'products';
}
