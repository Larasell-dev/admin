<?php

namespace Larasell\Admin\Http\Requests\ContentLocale;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Larasell\Admin\ContentLocale\AvailableLocales;
use Larasell\Admin\Payloads\ContentLocale\SetContentLocalePayload;
use Larasell\Larasell\Enums\Locale;

class SetContentLocaleRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(AvailableLocales $locales): array
    {
        return [
            'locale' => ['required', 'string', Rule::in(array_map(fn (Locale $locale): string => $locale->value, $locales->all()))],
        ];
    }

    public function payload(): SetContentLocalePayload
    {
        return new SetContentLocalePayload(Locale::from($this->validated('locale')));
    }
}
