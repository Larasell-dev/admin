<?php

namespace Larasell\Admin\SalesChannel\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Larasell\Admin\SalesChannel\Actions\UpdateSalesChannelPayload;
use Larasell\Larasell\Settings\CurrencySettings;
use Larasell\Larasell\Settings\LocaleSettings;

/**
 * Editing a sales channel: its name, the locales and currencies it sells in with a default of
 * each, and whether it's active.
 */
class UpdateSalesChannelRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $locales = array_map(fn ($locale): string => $locale->value, app(LocaleSettings::class)->supported());

        return [
            'name' => ['required', 'string', 'max:255'],
            'locales' => ['required', 'array', 'min:1'],
            'locales.*' => ['string', 'distinct', Rule::in($locales)],
            'currencies' => ['required', 'array', 'min:1'],
            'currencies.*' => ['string', 'distinct', Rule::in(app(CurrencySettings::class)->enabledCodes())],
            'default_locale' => ['required', 'string', Rule::in($this->array('locales'))],
            'default_currency' => ['required', 'string', Rule::in($this->array('currencies'))],
            'active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'locales.required' => __('Pick at least one language.'),
            'currencies.required' => __('Pick at least one currency.'),
            'default_locale.in' => __('The default language must be one the channel sells in.'),
            'default_currency.in' => __('The default currency must be one the channel sells in.'),
        ];
    }

    public function payload(): UpdateSalesChannelPayload
    {
        return new UpdateSalesChannelPayload(
            name: $this->string('name')->value(),
            locales: array_values($this->array('locales')),
            currencies: array_values($this->array('currencies')),
            defaultLocale: $this->string('default_locale')->value(),
            defaultCurrency: $this->string('default_currency')->value(),
            active: $this->boolean('active'),
        );
    }
}
