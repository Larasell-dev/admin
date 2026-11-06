<?php

namespace Workbench\App\ProductNotes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Larasell\Admin\Platform\Sections\Props;
use Larasell\Admin\Platform\Sections\SectionContext;

/**
 * What the notes section shows: the product's note, none on the create page.
 */
class ProductNotesProps implements Props
{
    /**
     * @return array{note: ?string}
     */
    public function resolve(?Model $resource, SectionContext $context): array
    {
        return [
            'note' => $resource === null ? null : DB::table('workbench_product_notes')->where('product_id', $resource->getKey())->value('note'),
        ];
    }
}
