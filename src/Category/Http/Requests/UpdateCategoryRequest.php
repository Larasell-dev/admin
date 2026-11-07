<?php

namespace Larasell\Admin\Category\Http\Requests;

use Larasell\Larasell\Catalog\Actions\UpdateCategoryPayload;
use Larasell\Larasell\Catalog\Models\Category;
use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\Enums\Visibility;

/**
 * Updates a category's name and visibility; it keeps its place in the tree, see MoveCategoryRequest.
 */
class UpdateCategoryRequest extends CategoryRequest
{
    /**
     * The category's name in the content locale, keeping its other translations.
     */
    public function payload(Category $category, Locale $locale): UpdateCategoryPayload
    {
        return new UpdateCategoryPayload(
            name: $category->name->with($locale->value, $this->validated('name')),
            status: Visibility::from($this->validated('status')),
            attachments: $this->attachments(),
        );
    }
}
