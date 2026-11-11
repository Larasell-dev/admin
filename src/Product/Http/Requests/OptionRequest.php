<?php

namespace Larasell\Admin\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Larasell\Admin\Platform\ContentLocale\Resolvers\ContentLocaleResolver;
use Larasell\Chronicle\Exceptions\StructuredLogicException;
use Larasell\Larasell\Catalog\Options\Actions\OptionValueDraft;
use Larasell\Larasell\Catalog\Options\Actions\SaveOptionDefinitionPayload;
use Larasell\Larasell\Catalog\Options\Models\OptionDefinition;
use Larasell\Larasell\Catalog\Options\OptionDefinitionValueId;
use Larasell\Larasell\Catalog\Options\OptionDisplay;
use Larasell\Larasell\Enums\Locale;
use Larasell\Larasell\Translatable;

/** Creates or changes an option with all of its values, their swatches included. */
class OptionRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $option = $this->option();

        return [
            // Left empty, it has no name in the content locale and the store shows another language's.
            'name' => [$option?->name->without($this->locale()->value) === null ? 'required' : 'nullable', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:64', 'regex:'.AttributeRequest::SLUG, Rule::unique(OptionDefinition::class)->ignore($option)],
            'display' => ['required', Rule::enum(OptionDisplay::class)],
            // All of its values, in order: with the id of one it has, or without one for a new one.
            // Values left out are deleted, while no variant uses them.
            'values' => ['present', 'array'],
            'values.*.id' => ['nullable', 'integer', 'distinct', Rule::in($option?->values()->pluck('id')->all() ?? [])],
            'values.*.name' => ['nullable', 'string', 'max:255'],
            'values.*.slug' => ['required', 'string', 'max:64', 'distinct', 'regex:'.AttributeRequest::SLUG],
            'values.*.swatch' => ['nullable', 'regex:/^#(?:[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/'],
        ];
    }

    /** The option with its values for SaveOptionDefinition, each name in the given locale with its other translations kept. */
    public function payload(Locale $locale): SaveOptionDefinitionPayload
    {
        $option = $this->option();
        $existing = $option?->values->keyBy('id');
        // validated() rebuilds the list rule by rule, so values with an id come first; the keys keep the order.
        $values = $this->validated('values');
        ksort($values);
        // Without text, the locale's translation goes; validation keeps one in another locale.
        $name = fn (?Translatable $stored, ?string $text): Translatable => $text === null
            ? ($stored?->without($locale->value) ?? throw new StructuredLogicException(
                errorCode: 'larasell-admin.product.option-name-missing',
                message: 'An option or value without a name in the content locale has no other one.',
                why: 'OptionRequest requires a name where no other locale has one, so this is a bug.',
            ))
            : ($stored?->with($locale->value, $text) ?? new Translatable([$locale->value => $text]));

        return new SaveOptionDefinitionPayload(
            name: $name($option?->name, $this->validated('name')),
            slug: $this->validated('slug'),
            display: OptionDisplay::from($this->validated('display')),
            values: array_values(array_map(function (array $value) use ($existing, $name): OptionValueDraft {
                $id = isset($value['id']) ? (int) $value['id'] : null;
                $swatch = $value['swatch'] ?? null;

                return new OptionValueDraft(
                    id: $id === null ? null : new OptionDefinitionValueId($id),
                    name: $name($id === null ? null : $existing?->get($id)?->name, $value['name'] ?? null),
                    slug: $value['slug'],
                    swatch: $swatch === null ? null : strtolower($swatch),
                );
            }, $values)),
        );
    }

    /**
     * A value without a name in the content locale needs one in another locale: a new value has none yet.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $existing = $this->option()?->values->keyBy('id');

            foreach ((array) $this->input('values', []) as $index => $value) {
                $id = is_array($value) ? ($value['id'] ?? null) : null;
                $empty = ! is_array($value) || trim((string) ($value['name'] ?? '')) === '';

                if ($empty && ($id === null || $existing?->get((int) $id)?->name->without($this->locale()->value) === null)) {
                    $validator->errors()->add("values.{$index}.name", __('validation.required', ['attribute' => __('name')]));
                }
            }
        }];
    }

    private function locale(): Locale
    {
        return app(ContentLocaleResolver::class)->resolve($this);
    }

    private function option(): ?OptionDefinition
    {
        $option = $this->route('option');

        return $option instanceof OptionDefinition ? $option : null;
    }
}
