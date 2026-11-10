<?php

namespace Larasell\Admin\Customer\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Larasell\Larasell\Exceptions\InvalidPhoneNumber;
use Larasell\Larasell\PhoneNumber;

/** Passes what the core accepts as a PhoneNumber: digits, spaces and + ( ) - / . */
class ValidPhoneNumber implements ValidationRule
{
    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            PhoneNumber::of(is_string($value) ? $value : '');
        } catch (InvalidPhoneNumber) {
            $fail(__('Enter a phone number with digits, spaces and + ( ) - / . only.'));
        }
    }
}
