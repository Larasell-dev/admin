<?php

namespace Larasell\Admin\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use Larasell\Admin\Platform\ContentLocale\Resolvers\ContentLocaleResolver;
use Larasell\Admin\Product\Exceptions\OptionNameMissing;
use Larasell\Larasell\Catalog\Options\Actions\OptionValueDraft;
use Larasell\Larasell\Catalog\Options\Actions\SaveOptionDefinition;
use Larasell\Larasell\Catalog\Options\Actions\SaveOptionDefinitionPayload;
use Larasell\Larasell\Catalog\Options\Exceptions\OptionInUse;
use Larasell\Larasell\Catalog\Options\Models\OptionDefinition;
use Larasell\Larasell\Catalog\Options\OptionDefinitionId;
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

    /**
     * Saves the option with all of its values, as the form error on the values when it leaves
     * out one that variants use.
     *
     * @throws ValidationException
     */
    public function save(SaveOptionDefinition $save, Locale $locale): void
    {
        // Only the update route saves; it always has an option.
        $option = $this->option() ?? abort(404);

        try {
            $save->handle(OptionDefinitionId::of($option), $this->payload($locale));
        } catch (OptionInUse $exception) {
            $value = $option->values()->where('slug', $exception->value)->first();

            throw ValidationException::withMessages(['values' => self::inUse($value?->name->get($locale->value) ?? (string) $exception->value, $exception)]);
        }
    }

    /** Says which variants of how many products still use an option or value. */
    public static function inUse(string $name, OptionInUse $exception): string
    {
        return __(':name is used by :variants of :products. Delete those variants on the product first.', [
            'name' => $name,
            'variants' => trans_choice('{1} :count variant|[2,*] :count variants', $exception->variants),
            'products' => trans_choice('{1} :count product|[2,*] :count products', $exception->products),
        ]);
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
            ? ($stored?->without($locale->value) ?? throw OptionNameMissing::inEveryLocale())
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
