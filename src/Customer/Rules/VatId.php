<?php

namespace Larasell\Admin\Customer\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Larasell\Larasell\Exceptions\InvalidVatId;
use Larasell\Larasell\VatId as VatIdValue;

/**
 * Passes a VAT ID in the format of an EU country or Northern Ireland, e.g. "DE 123 456 789". Only
 * the format is checked, not whether the number is registered.
 */
class VatId implements ValidationRule
{
    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            VatIdValue::of(is_string($value) ? $value : '');
        } catch (InvalidVatId) {
            $fail(__('Enter a VAT ID such as DE123456789.'));
        }
    }
}
