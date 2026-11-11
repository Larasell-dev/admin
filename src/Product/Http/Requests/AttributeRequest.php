<?php

namespace Larasell\Admin\Product\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Larasell\Admin\Platform\ContentLocale\Resolvers\ContentLocaleResolver;
use Larasell\Chronicle\Exceptions\StructuredLogicException;
use Larasell\Larasell\Catalog\Actions\AttributeValueDraft;
use Larasell\Larasell\Catalog\Actions\SaveProductAttributePayload;
use Larasell\Larasell\Catalog\AttributeCardinality;
use Larasell\Larasell\Catalog\AttributeScope;
use Larasell\Larasell\Catalog\AttributeValueId;
use Larasell\Larasell\Catalog\Models\ProductAttribute;
use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\Enums\ProductAttributeType;
use Larasell\Larasell\Translatable;

/**
 * Creates or changes an attribute with all of its values. A change that would leave values behind that products or
 * variants no longer show, such as several values of a single-value attribute, is refused.
 */
class AttributeRequest extends FormRequest
{
    public const string SLUG = '/^[a-z0-9]+(?:[-_][a-z0-9]+)*$/';

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $attribute = $this->attribute();

        return [
            // Left empty, it has no name in the content locale and the store shows another language's.
            // Picked when creating; it can't change.
            'type' => $attribute === null ? ['required', Rule::enum(ProductAttributeType::class)] : ['exclude'],
            'name' => [$attribute?->name->without($this->locale()->value) === null ? 'required' : 'nullable', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:64', 'regex:'.self::SLUG, Rule::unique(ProductAttribute::class)->ignore($attribute)],
            'scope' => ['required', Rule::enum(AttributeScope::class), function (string $field, mixed $value, Closure $fail) use ($attribute): void {
                if ($attribute === null) {
                    return;
                }

                // Products-only attributes ignore variants' values, and variants-only ones products'.
                if ($value === AttributeScope::Product->value && ($count = $this->variantsWithValues($attribute)) > 0) {
                    $fail(trans_choice('{1} A variant has its own values of this attribute, which it would no longer show. Remove them first.|[2,*] :count variants have their own values of this attribute, which they would no longer show. Remove them first.', $count));
                } elseif ($value === AttributeScope::Variant->value && ($count = $this->productsWithValues($attribute)) > 0) {
                    $fail(trans_choice('{1} A product has values of this attribute, which it would no longer show. Remove them first.|[2,*] :count products have values of this attribute, which they would no longer show. Remove them first.', $count));
                }
            }],
            'multiple' => ['required', 'boolean', function (string $field, mixed $value, Closure $fail) use ($attribute): void {
                // Back to one value only once no product or variant has several.
                if ($attribute !== null && ! filter_var($value, FILTER_VALIDATE_BOOL) && $this->hasSeveralValues($attribute)) {
                    $fail(__('Some products or variants have several values of this attribute. Leave them one each first.'));
                }
            }],
            // All of its values, in order: with the id of one it has, or without one for a new one.
            // Values left out are deleted.
            // Yes-or-no attributes have exactly their yes and no, which can only be renamed.
            'values' => ['present', 'array', ...($this->type() === ProductAttributeType::Boolean ? ['size:2'] : [])],
            'values.*.id' => ['nullable', 'integer', 'distinct', Rule::in($attribute?->values()->pluck('id')->all() ?? [])],
            'values.*.name' => ['nullable', 'string', 'max:255', function (string $field, mixed $value, Closure $fail): void {
                // A number attribute's values are the numbers their names spell.
                if ($this->type() === ProductAttributeType::Number && ! is_numeric($value)) {
                    $fail(__('Enter a number, e.g. 180 or 2.5.'));
                }
            }],
            'values.*.slug' => ['required', 'string', 'max:64', 'distinct', ...($this->type() === ProductAttributeType::Boolean
                ? [Rule::in(['__boolean_true', '__boolean_false'])]
                : ['regex:'.self::SLUG])],
        ];
    }

    /**
     * The attribute with its values for SaveProductAttribute, each name in the given locale with
     * its other translations kept.
     */
    public function payload(Locale $locale): SaveProductAttributePayload
    {
        $attribute = $this->attribute();
        $existing = $attribute?->values->keyBy('id');
        // validated() rebuilds the list rule by rule, so values with an id come first; the keys keep the order.
        $values = $this->validated('values');
        ksort($values);
        // Without text, the locale's translation goes; validation keeps one in another locale.
        $name = fn (?Translatable $stored, ?string $text): Translatable => $text === null
            ? ($stored?->without($locale->value) ?? throw new StructuredLogicException(
                errorCode: 'larasell-admin.product.attribute-name-missing',
                message: 'An attribute or value without a name in the content locale has no other one.',
                why: 'AttributeRequest requires a name where no other locale has one, so this is a bug.',
            ))
            : ($stored?->with($locale->value, $text) ?? new Translatable([$locale->value => $text]));

        return new SaveProductAttributePayload(
            name: $name($attribute?->name, $this->validated('name')),
            slug: $this->validated('slug'),
            type: $this->type(),
            scope: AttributeScope::from($this->validated('scope')),
            cardinality: $this->boolean('multiple') ? AttributeCardinality::Multiple : AttributeCardinality::Single,
            values: array_values(array_map(function (array $value) use ($existing, $name): AttributeValueDraft {
                $id = isset($value['id']) ? (int) $value['id'] : null;

                return new AttributeValueDraft(
                    id: $id === null ? null : new AttributeValueId($id),
                    name: $name($id === null ? null : $existing?->get($id)?->name, $value['name'] ?? null),
                    slug: $value['slug'],
                );
            }, $values)),
        );
    }

    /**
     * A value without a name in the content locale needs one in another locale: a new value has
     * none yet.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $existing = $this->attribute()?->values->keyBy('id');

            foreach ((array) $this->input('values', []) as $index => $value) {
                $id = is_array($value) ? ($value['id'] ?? null) : null;
                $empty = ! is_array($value) || trim((string) ($value['name'] ?? '')) === '';

                if ($empty && ($id === null || $existing?->get((int) $id)?->name->without($this->locale()->value) === null)) {
                    $validator->errors()->add("values.{$index}.name", __('validation.required', ['attribute' => __('name')]));
                }
            }
        }];
    }

    /** The attribute's own type, or for a new one the type picked, text while it's missing. */
    private function type(): ProductAttributeType
    {
        return $this->attribute()->type
            ?? ProductAttributeType::tryFrom((string) $this->input('type'))
            ?? ProductAttributeType::Text;
    }

    private function locale(): Locale
    {
        return app(ContentLocaleResolver::class)->resolve($this);
    }

    private function attribute(): ?ProductAttribute
    {
        $attribute = $this->route('attribute');

        return $attribute instanceof ProductAttribute ? $attribute : null;
    }

    private function productsWithValues(ProductAttribute $attribute): int
    {
        return DB::table('larasell_product_product_attribute_value')
            ->join('larasell_product_attribute_values', 'larasell_product_attribute_values.id', '=', 'larasell_product_product_attribute_value.product_attribute_value_id')
            ->where('larasell_product_attribute_values.product_attribute_id', $attribute->id)
            ->distinct()->count('larasell_product_product_attribute_value.product_id');
    }

    private function variantsWithValues(ProductAttribute $attribute): int
    {
        return DB::table('larasell_product_variant_attribute_values')
            ->where('product_attribute_id', $attribute->id)
            ->distinct()->count('product_variant_id');
    }

    private function hasSeveralValues(ProductAttribute $attribute): bool
    {
        return DB::table('larasell_product_product_attribute_value')
            ->join('larasell_product_attribute_values', 'larasell_product_attribute_values.id', '=', 'larasell_product_product_attribute_value.product_attribute_value_id')
            ->where('larasell_product_attribute_values.product_attribute_id', $attribute->id)
            ->groupBy('larasell_product_product_attribute_value.product_id')->havingRaw('count(*) > 1')->exists()
            || DB::table('larasell_product_variant_attribute_values')
                ->where('product_attribute_id', $attribute->id)->whereNotNull('product_attribute_value_id')
                ->groupBy('product_variant_id')->havingRaw('count(*) > 1')->exists();
    }
}
