<?php

namespace Larasell\Admin\Category\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
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
        return ['confirmation' => ['nullable', 'string']];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            /** @var Category $category */
            $category = $this->route('category');
            $name = $category->name->get();

            if ($this->string('confirmation')->value() !== $name) {
                $validator->errors()->add('confirmation', __('Type ":text" to confirm.', ['text' => $name]));
            }
        }];
    }
}
