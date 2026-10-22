<?php

namespace Larasell\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Larasell\Larasell\Models\Category;
use Larasell\Larasell\Models\Product;

class AddProductToCategoryRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var Category $category */
        $category = $this->route('category');

        return [
            'product_id' => [
                'required',
                'integer',
                Rule::exists(Product::class, 'id'),
                Rule::unique('larasell_category_product', 'product_id')->where('category_id', $category->getKey()),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.unique' => 'The product is already in this category.',
        ];
    }
}
