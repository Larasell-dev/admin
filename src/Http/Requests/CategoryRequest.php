<?php

namespace Larasell\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\Rule;
use Larasell\Larasell\Models\Category;

class CategoryRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $category = $this->route('category');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                // The storefront finds categories by their slug in the current locale.
                Rule::unique(Category::class, 'slug->'.App::currentLocale())
                    ->ignore($category instanceof Category ? $category->id : null),
            ],
            'visible' => ['boolean'],
            // Only creating reads it; a category keeps its place in the tree when it's updated.
            'parent_id' => ['nullable', 'integer', Rule::exists(Category::class, 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex' => 'The slug may only contain lowercase letters, numbers, and single dashes between them.',
        ];
    }
}
