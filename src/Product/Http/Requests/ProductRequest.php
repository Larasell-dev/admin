<?php

namespace Larasell\Admin\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Larasell\Admin\Platform\ContentLocale\CurrentContentLocale;
use Larasell\Admin\Platform\Sections\SectionContext;
use Larasell\Admin\Platform\Sections\SectionForm;
use Larasell\Admin\Platform\Sections\SectionRequests;
use Larasell\Admin\Platform\Sections\Sections;
use Larasell\Admin\Product\Rules\Barcode;
use Larasell\Larasell\Attachments\Attachments;
use Larasell\Larasell\Barcode as LarasellBarcode;
use Larasell\Larasell\Catalog\Models\Category;
use Larasell\Larasell\Catalog\Models\Product;
use Larasell\Larasell\Dimensions;
use Larasell\Larasell\Enums\LengthUnit;
use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\Enums\Visibility;
use Larasell\Larasell\Enums\WeightUnit;
use Larasell\Larasell\Weight;

class ProductRequest extends FormRequest
{
    /** A non-negative decimal with a dot, the only amounts the library's measurements take. */
    private const string AMOUNT = 'regex:/^\\d+(\\.\\d+)?$/';

    private ?SectionRequests $sections = null;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
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
            // The form leaves it out when no category is selected.
            'categories' => ['sometimes', 'array'],
            'categories.*' => ['integer', 'distinct', Rule::exists(Category::class, 'id')],
        ];
    }

    /**
     * Reduces a valid barcode to its digits, so it's compared and saved the way the library
     * writes it, whatever spaces or dashes it was entered with.
     */
    protected function prepareForValidation(): void
    {
        $this->sections()->prepare($this);

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
     * Validates the sections that modules and plugins add to the page, each on its own fields
     * under sections[{key}], so the product only saves when they're valid too.
     *
     * @return list<callable>
     */
    public function after(): array
    {
        return [$this->sections()->validate()];
    }

    /**
     * The sections' payloads, passed along with the core's action, e.g. the product's custom URLs.
     */
    public function attachments(): Attachments
    {
        return new Attachments(...$this->sections()->payloads());
    }

    /**
     * The requests of the page's sections, which the product's saved event hands to them.
     */
    public function sections(): SectionRequests
    {
        return $this->sections ??= app()->call(fn (Sections $sections, #[CurrentContentLocale] Locale $locale): SectionRequests => $sections->requests(
            SectionForm::Product,
            $this,
            $this->route('product') instanceof Product ? $this->route('product') : null,
            new SectionContext($locale),
        ));
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $amount = __('The :attribute must be a number of 0 or more, with a dot for decimals.');
        $axis = __('Enter a :attribute too, or leave length, width and height empty.');

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
            'weight.amount' => __('weight'),
            'weight.unit' => __('weight unit'),
            'dimensions.length' => __('length'),
            'dimensions.width' => __('width'),
            'dimensions.height' => __('height'),
            'dimensions.unit' => __('dimensions unit'),
            'allow_backorders' => __('backorders'),
            'min_quantity' => __('minimum quantity'),
            'max_quantity' => __('maximum quantity'),
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
     * The ids of the categories the product is in, none when the field is left out.
     *
     * @return array<int, int>
     */
    public function categoryIds(): array
    {
        return array_map(intval(...), $this->validated('categories', []));
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
