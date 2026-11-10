<?php

namespace Larasell\Admin\Customer\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Larasell\Larasell\Exceptions\InvalidUrl;
use Larasell\Larasell\Url;

/** Passes what the core accepts as a Url; a value without a scheme gets https://. */
class ValidUrl implements ValidationRule
{
    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            Url::of(is_string($value) ? $value : '');
        } catch (InvalidUrl) {
            $fail(__('Enter a web address such as example.com.'));
        }
    }
}
