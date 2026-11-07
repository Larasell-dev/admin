<?php

namespace Larasell\Admin\Category\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Larasell\Larasell\Catalog\Models\Category;
use Larasell\Larasell\Enums\Visibility;

class CategoryRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::enum(Visibility::class)],
            // Only creating reads it; a category keeps its place in the tree when it's updated.
            'parent_id' => ['nullable', 'integer', Rule::exists(Category::class, 'id')],
        ];
    }
}
