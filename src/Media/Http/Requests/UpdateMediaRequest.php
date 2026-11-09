<?php

namespace Larasell\Admin\Media\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\Media\Actions\UpdateMediaPayload;
use Larasell\Larasell\Media\AssetVisibility;
use Larasell\Larasell\Media\Models\Media;
use Larasell\Larasell\Translatable;

/** Changes a media item's alt text in the content locale, and whether its files are public. */
class UpdateMediaRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'alt' => ['nullable', 'string', 'max:255'],
            'visibility' => ['sometimes', Rule::enum(AssetVisibility::class)],
        ];
    }

    /**
     * The title, description and alt text in the content locale, each keeping its other
     * translations. An empty one removes the locale's, so the storefront falls back to another.
     */
    public function payload(Media $media, Locale $locale): UpdateMediaPayload
    {
        return new UpdateMediaPayload(
            alt: $this->inLocale('alt', $media->alt, $locale),
            title: $this->inLocale('title', $media->title, $locale),
            description: $this->inLocale('description', $media->description, $locale),
            visibility: $this->has('visibility') ? AssetVisibility::from($this->string('visibility')->value()) : null,
        );
    }

    private function inLocale(string $field, ?Translatable $current, Locale $locale): ?Translatable
    {
        $value = trim($this->string($field)->value());

        if ($value === '') {
            return $current?->without($locale->value);
        }

        return $current?->with($locale->value, $value) ?? Translatable::fromString($value, $locale->value);
    }
}
