<?php

namespace Larasell\Admin\Product\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * The product form's own sections, in the order the form shows them. Sections that modules and
 * plugins add go before or after one of them: Section::add(...)->after(ProductSection::Stock).
 *
 * @public
 */
#[TypeScript]
enum ProductSection: string
{
    /** The name and visibility. */
    case General = 'general';

    case Categories = 'categories';

    /** The stock level, backorders and order quantity limits. */
    case Stock = 'stock';

    case WeightAndDimensions = 'weight-and-dimensions';

    /** The SKU and barcode. */
    case Identifiers = 'identifiers';
}
