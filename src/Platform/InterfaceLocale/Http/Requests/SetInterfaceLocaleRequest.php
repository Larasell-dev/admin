<?php

namespace Larasell\Admin\Platform\InterfaceLocale\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Larasell\Admin\Platform\InterfaceLocale\InterfaceLocale;
use Larasell\Admin\Platform\InterfaceLocale\Locales\AvailableInterfaceLocales;

class SetInterfaceLocaleRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(AvailableInterfaceLocales $locales): array
    {
        return [
            'locale' => ['required', 'string', Rule::in(array_map(fn (InterfaceLocale $locale): string => $locale->code, $locales->all()))],
        ];
    }

    public function interfaceLocale(): InterfaceLocale
    {
        return new InterfaceLocale($this->validated('locale'));
    }
}
