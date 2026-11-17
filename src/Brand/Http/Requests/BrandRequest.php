<?php

namespace Larasell\Admin\Brand\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Larasell\Admin\Platform\ContentLocale\Resolvers\ContentLocaleResolver;
use Larasell\Larasell\Catalog\Brands\Actions\CreateBrandPayload;
use Larasell\Larasell\Catalog\Brands\Actions\UpdateBrandPayload;
use Larasell\Larasell\Catalog\Brands\Models\Brand;
use Larasell\Larasell\Translatable;

/** Creates or changes a brand; its name and description change in the content locale only. */
class BrandRequest extends FormRequest
{
    /** Lowercase letters and numbers, joined by - or _. */
    private const string SLUG = '/^[a-z0-9]+(?:[-_][a-z0-9]+)*$/';

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $brand = $this->brand();
        $locale = app(ContentLocaleResolver::class)->resolve($this)->value;

        return [
            // Left empty, it has no name in the content locale and the store shows another language's.
            'name' => [$brand?->name->without($locale) === null ? 'required' : 'nullable', 'string', 'max:255'],
            ...self::newRules(),
            'slug' => ['required', 'string', 'max:64', 'regex:'.self::SLUG, Rule::unique(Brand::class)->ignore($brand)],
        ];
    }

    /**
     * A brand's fields, under $prefix, e.g. new_brand. for one added on the product form, or
     * brand_changes. for $brand changed there, which may keep its own slug.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function newRules(string $prefix = '', ?Brand $brand = null): array
    {
        return [
            "{$prefix}name" => ['required', 'string', 'max:255'],
            "{$prefix}slug" => ['required', 'string', 'max:64', 'regex:'.self::SLUG, Rule::unique(Brand::class, 'slug')->ignore($brand)],
            "{$prefix}description" => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function createPayload(string $locale): CreateBrandPayload
    {
        return self::newPayload($this->validated(), $locale);
    }

    /** @param  array<array-key, mixed>  $fields  Validated with newRules(). */
    public static function newPayload(array $fields, string $locale): CreateBrandPayload
    {
        $description = $fields['description'] ?? null;

        return new CreateBrandPayload(
            name: new Translatable([$locale => (string) $fields['name']]),
            slug: (string) $fields['slug'],
            description: is_string($description) && $description !== '' ? new Translatable([$locale => $description]) : null,
        );
    }

    /** Keeps the name's and description's other translations; an empty description drops this one. */
    public function updatePayload(Brand $brand, string $locale): UpdateBrandPayload
    {
        return self::changesPayload($brand, $this->validated(), $locale);
    }

    /** @param  array<array-key, mixed>  $fields  Validated with rules() or newRules(). */
    public static function changesPayload(Brand $brand, array $fields, string $locale): UpdateBrandPayload
    {
        $name = is_string($fields['name'] ?? null) && $fields['name'] !== '' ? $fields['name'] : null;
        $description = is_string($fields['description'] ?? null) && $fields['description'] !== '' ? $fields['description'] : null;

        return new UpdateBrandPayload(
            name: $name === null ? $brand->name->without($locale) : $brand->name->with($locale, $name),
            slug: (string) $fields['slug'],
            description: $description === null
                ? $brand->description?->without($locale)
                : ($brand->description?->with($locale, $description) ?? new Translatable([$locale => $description])),
        );
    }

    private function brand(): ?Brand
    {
        $brand = $this->route('brand');

        return $brand instanceof Brand ? $brand : null;
    }
}
