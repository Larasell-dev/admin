<?php

namespace Larasell\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\Rule;
use Larasell\Admin\Rules\Slug;
use Larasell\Larasell\Enums\Visibility;
use Larasell\Larasell\Models\Product;

class ProductRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                new Slug,
                // The storefront finds products by their slug in the current locale.
                Rule::unique(Product::class, 'slug->'.App::currentLocale())
                    ->ignore($product instanceof Product ? $product->id : null),
            ],
            'status' => ['required', Rule::enum(Visibility::class)],
        ];
    }
}
