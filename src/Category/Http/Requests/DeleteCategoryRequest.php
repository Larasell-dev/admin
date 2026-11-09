<?php

namespace Larasell\Admin\Category\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Larasell\Admin\Platform\Panel\Rules\Confirmation;
use Larasell\Larasell\Catalog\Models\Category;

/**
 * Deleting a category deletes every category below it too, so the admin types its name first.
 */
class DeleteCategoryRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $category = $this->route('category');
        assert($category instanceof Category);

        return ['confirmation' => [new Confirmation($category->name->get())]];
    }
}
