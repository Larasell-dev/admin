<?php

namespace Larasell\Admin\Product\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Larasell\Admin\Platform\ContentLocale\CurrentContentLocale;
use Larasell\Admin\Platform\RichText\Rules\RichTextDocument;
use Larasell\Admin\Platform\Sections\SectionContext;
use Larasell\Admin\Platform\Sections\SectionForm;
use Larasell\Admin\Platform\Sections\SectionRequests;
use Larasell\Admin\Platform\Sections\Sections;
use Larasell\Admin\Product\Rules\Barcode;
use Larasell\Larasell\Attachments\Attachments;
use Larasell\Larasell\Barcode as LarasellBarcode;
use Larasell\Larasell\Catalog\AttributeCardinality;
use Larasell\Larasell\Catalog\CoreProductType;
use Larasell\Larasell\Catalog\Models\Category;
use Larasell\Larasell\Catalog\Models\Product;
use Larasell\Larasell\Catalog\Models\ProductAttribute;
use Larasell\Larasell\Catalog\Models\ProductAttributeValue;
use Larasell\Larasell\Catalog\Models\ProductVariant;
use Larasell\Larasell\Catalog\ProductType;
use Larasell\Larasell\Dimensions;
use Larasell\Larasell\Enums\Country;
use Larasell\Larasell\Enums\LengthUnit;
use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\Enums\Visibility;
use Larasell\Larasell\Enums\WeightUnit;
use Larasell\Larasell\Facades\ProductTypes;
use Larasell\Larasell\Inventory\InventoryCapabilities;
use Larasell\Larasell\RichText\RichText;
use Larasell\Larasell\Shipping\ShippingCapabilities;
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
        // Fields of capabilities the type lacks are dropped, e.g. a weight sent for a digital product.
        $without = fn (string $capability): array => $this->productType()->has($capability) ? [] : ['exclude'];
        $stock = $without(InventoryCapabilities::STOCK);
        $dimensions = $without(ShippingCapabilities::DIMENSIONS);

        return [
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::enum(Visibility::class)],
            // The editor's document as JSON. Left out, the product keeps its description.
            'description' => ['sometimes', 'nullable', 'string', new RichTextDocument],
            // Optional, since digital products have neither. The units always come with the
            // form, so only the amounts decide whether there is a weight or dimensions.
            'weight' => [...$dimensions, 'nullable', 'array'],
            'weight.amount' => [...$dimensions, 'nullable', 'string', self::AMOUNT],
            'weight.unit' => [...$dimensions, 'required_with:weight.amount', Rule::enum(WeightUnit::class)],
            'dimensions' => [...$dimensions, 'nullable', 'array'],
            // All three or none, since Dimensions needs every axis.
            'dimensions.length' => [...$dimensions, 'nullable', 'string', self::AMOUNT, 'required_with:dimensions.width,dimensions.height'],
            'dimensions.width' => [...$dimensions, 'nullable', 'string', self::AMOUNT, 'required_with:dimensions.length,dimensions.height'],
            'dimensions.height' => [...$dimensions, 'nullable', 'string', self::AMOUNT, 'required_with:dimensions.length,dimensions.width'],
            'dimensions.unit' => [...$dimensions, 'required_with:dimensions.length,dimensions.width,dimensions.height', Rule::enum(LengthUnit::class)],
            // Optional, but each identifies one product, so no other product may have it.
            // Saved on the default variant, so unique among all variants but that one.
            'sku' => ['nullable', 'string', 'max:255', Rule::unique(ProductVariant::class)->ignore($this->defaultVariantId())],
            'barcode' => ['nullable', 'string', new Barcode, Rule::unique(ProductVariant::class)->ignore($this->defaultVariantId())],
            'mpn' => ['nullable', 'string', 'max:255'],
            // Customs details for shipping abroad, which variants inherit. Left out, they stay.
            'country_of_origin' => [...$dimensions, 'sometimes', 'nullable', Rule::enum(Country::class)],
            'hs_code' => [...$dimensions, 'sometimes', 'nullable', 'string', 'max:16'],
            // An empty stock isn't tracked. Backorders sell past 0, so the stock may be below it.
            'stock' => [...$stock, 'nullable', 'integer'],
            // Left out, a new product allows backorders and an existing one keeps its setting.
            'allow_backorders' => [...$stock, 'sometimes', 'boolean'],
            'min_quantity' => [...$stock, 'nullable', 'integer', 'min:1'],
            'max_quantity' => [...$stock, 'nullable', 'integer', 'min:1', 'gte:min_quantity'],
            // The form leaves it out when no category is selected.
            'categories' => ['sometimes', 'array'],
            'categories.*' => ['integer', 'distinct', Rule::exists(Category::class, 'id')],
            // By attribute ID: one of that attribute's values, a list of them for multi-value
            // attributes, or empty for none.
            'attributes' => ['sometimes', 'array'],
            'attributes.*' => ['nullable', function (string $attribute, mixed $value, Closure $fail): void {
                $ids = array_filter(Arr::wrap($value), fn (mixed $id): bool => $id !== null && $id !== '');
                $of = ProductAttribute::query()->find((int) Str::after($attribute, 'attributes.'));

                if ($of === null || array_filter($ids, fn (mixed $id): bool => ! is_numeric($id)) !== []
                    || ProductAttributeValue::query()->whereKey($ids)->where('product_attribute_id', $of->id)->count() !== count(array_unique($ids))) {
                    $fail(__('validation.exists', ['attribute' => __('attribute')]));
                } elseif (count($ids) > 1 && $of->cardinality === AttributeCardinality::Single) {
                    $fail(__(':attribute takes one value.', ['attribute' => $of->name->get(app()->getLocale())]));
                }
            }],
        ];
    }

    /** The edited product's default variant, which holds a simple product's identifiers. */
    private function defaultVariantId(): ?int
    {
        $product = $this->route('product');

        return $product instanceof Product ? $product->defaultVariant()->id : null;
    }

    /**
     * The edited product's type, or for a new one the type in ?type=, physical without one.
     */
    public function productType(): ProductType
    {
        $product = $this->route('product');
        if ($product instanceof Product) {
            return $product->productType();
        }

        $key = $this->query('type', CoreProductType::Physical->value);

        return is_string($key) && ProductTypes::has($key) ? ProductTypes::get($key) : abort(404);
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
            new SectionContext($locale, $this->productType()->capabilities()),
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
            ...$this->has('allow_backorders') && $this->productType()->has(InventoryCapabilities::STOCK) ? ['allow_backorders' => $this->boolean('allow_backorders')] : [],
        ];
    }

    /**
     * Whether the form sent a description, which an existing product otherwise keeps.
     */
    public function hasDescription(): bool
    {
        return array_key_exists('description', $this->validated());
    }

    /**
     * The description as sent, or null when it was left empty, since an empty document has nothing to show.
     */
    public function description(): ?RichText
    {
        $description = $this->validated('description');
        $text = is_string($description) ? RichText::fromJson($description) : null;

        return $text === null || $text->isEmpty() ? null : $text;
    }

    /**
     * The customs fields the form sent, to save on the product.
     *
     * @return array{country_of_origin?: Country|null, hs_code?: string|null}
     */
    public function customs(): array
    {
        $customs = [];

        if (array_key_exists('country_of_origin', $this->validated())) {
            $country = $this->validated('country_of_origin');
            $customs['country_of_origin'] = $country === null ? null : Country::from($country);
        }

        if (array_key_exists('hs_code', $this->validated())) {
            $customs['hs_code'] = $this->validated('hs_code');
        }

        return $customs;
    }

    /**
     * The attribute values the form set, leaving out attributes it left empty.
     *
     * @return list<int>
     */
    public function attributeValueIds(): array
    {
        return array_values(array_map(intval(...), array_filter(
            Arr::flatten($this->validated('attributes', [])),
            fn (mixed $id): bool => $id !== null && $id !== '',
        )));
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
