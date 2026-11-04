<?php

namespace Larasell\Admin\Http\Requests\Categories;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Larasell\Admin\ContentLocale\CurrentContentLocale;
use Larasell\Admin\Rules\Slug;
use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\Enums\Visibility;
use Larasell\Larasell\Models\Category;

class CategoryRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(#[CurrentContentLocale] Locale $locale): array
    {
        $category = $this->route('category');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                new Slug,
                // The storefront finds categories by their slug in each locale, so it only needs to
                // be unique in the content locale.
                Rule::unique(Category::class, 'slug->'.$locale->value)
                    ->ignore($category instanceof Category ? $category->id : null),
            ],
            'status' => ['required', Rule::enum(Visibility::class)],
            // Only creating reads it; a category keeps its place in the tree when it's updated.
            'parent_id' => ['nullable', 'integer', Rule::exists(Category::class, 'id')],
        ];
    }
}
