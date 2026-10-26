<?php

namespace Larasell\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Larasell\Admin\Rules\Barcode;
use Larasell\Admin\Rules\Slug;
use Larasell\Larasell\Barcode as LarasellBarcode;
use Larasell\Larasell\Dimensions;
use Larasell\Larasell\Enums\LengthUnit;
use Larasell\Larasell\Enums\Visibility;
use Larasell\Larasell\Enums\WeightUnit;
use Larasell\Larasell\Models\Product;
use Larasell\Larasell\Weight;

class ProductRequest extends FormRequest
{
    /** A non-negative decimal with a dot, the only amounts the library's measurements take. */
    private const string AMOUNT = 'regex:/^\\d+(\\.\\d+)?$/';

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                new Slug,
                // The storefront finds products by their slug in the current locale. Updating
                // ignores the product's own slug; creating has no product, so ignores nothing.
                Rule::unique(Product::class, 'slug->'.App::currentLocale())->ignore($this->route('product')),
            ],
            'status' => ['required', Rule::enum(Visibility::class)],
            // Optional, since digital products have neither. The units always come with the
            // form, so only the amounts decide whether there is a weight or dimensions.
            'weight' => ['nullable', 'array'],
            'weight.amount' => ['nullable', 'string', self::AMOUNT],
            'weight.unit' => ['required_with:weight.amount', Rule::enum(WeightUnit::class)],
            'dimensions' => ['nullable', 'array'],
            // All three or none, since Dimensions needs every axis.
            'dimensions.length' => ['nullable', 'string', self::AMOUNT, 'required_with:dimensions.width,dimensions.height'],
            'dimensions.width' => ['nullable', 'string', self::AMOUNT, 'required_with:dimensions.length,dimensions.height'],
            'dimensions.height' => ['nullable', 'string', self::AMOUNT, 'required_with:dimensions.length,dimensions.width'],
            'dimensions.unit' => ['required_with:dimensions.length,dimensions.width,dimensions.height', Rule::enum(LengthUnit::class)],
            // Optional, but each identifies one product, so no other product may have it.
            'sku' => ['nullable', 'string', 'max:255', Rule::unique(Product::class)->ignore($this->route('product'))],
            'barcode' => ['nullable', 'string', new Barcode, Rule::unique(Product::class)->ignore($this->route('product'))],
            // An empty stock isn't tracked. Backorders sell past 0, so the stock may be below it.
            'stock' => ['nullable', 'integer'],
            // Left out, a new product allows backorders and an existing one keeps its setting.
            'allow_backorders' => ['sometimes', 'boolean'],
            'min_quantity' => ['nullable', 'integer', 'min:1'],
            'max_quantity' => ['nullable', 'integer', 'min:1', 'gte:min_quantity'],
        ];
    }

    /**
     * Reduces a valid barcode to its digits, so it's compared and saved the way the library
     * writes it, whatever spaces or dashes it was entered with.
     */
    protected function prepareForValidation(): void
    {
        $barcode = $this->input('barcode');

        try {
            if (is_string($barcode) && $barcode !== '') {
                $this->merge(['barcode' => LarasellBarcode::of($barcode)->value()]);
            }
        } catch (InvalidArgumentException) {
            // Left as entered for the Barcode rule to explain.
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $amount = 'The :attribute must be a number of 0 or more, with a dot for decimals.';
        $axis = 'Enter a :attribute too, or leave length, width and height empty.';

        return [
            'weight.amount.regex' => $amount,
            'dimensions.*.regex' => $amount,
            'dimensions.*.required_with' => $axis,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'weight.amount' => 'weight',
            'weight.unit' => 'weight unit',
            'dimensions.length' => 'length',
            'dimensions.width' => 'width',
            'dimensions.height' => 'height',
            'dimensions.unit' => 'dimensions unit',
            'allow_backorders' => 'backorders',
            'min_quantity' => 'minimum quantity',
            'max_quantity' => 'maximum quantity',
        ];
    }

    /**
     * The stock, whether it allows backorders, and the quantity limits, keyed as the product
     * takes them. Empty numbers are null: an untracked stock, or no limit.
     *
     * @return array{stock: ?int, min_quantity: ?int, max_quantity: ?int, allow_backorders?: bool}
     */
    public function stock(): array
    {
        $integer = fn (string $key): ?int => $this->validated($key) === null ? null : (int) $this->validated($key);

        return [
            'stock' => $integer('stock'),
            'min_quantity' => $integer('min_quantity'),
            'max_quantity' => $integer('max_quantity'),
            ...$this->has('allow_backorders') ? ['allow_backorders' => $this->boolean('allow_backorders')] : [],
        ];
    }

    /**
     * The weight as entered, without converting it, or null when its amount is left empty.
     */
    public function weight(): ?Weight
    {
        $amount = $this->validated('weight.amount');

        return $amount === null ? null : Weight::of($amount, $this->validated('weight.unit'));
    }

    /**
     * The dimensions as entered, without converting them, or null when they are left empty.
     */
    public function dimensions(): ?Dimensions
    {
        if ($this->validated('dimensions.length') === null) {
            return null;
        }

        return Dimensions::of(
            $this->validated('dimensions.length'),
            $this->validated('dimensions.width'),
            $this->validated('dimensions.height'),
            $this->validated('dimensions.unit'),
        );
    }
}
