<?php

namespace Larasell\Admin\Customer\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Larasell\Larasell\Customers\CustomerGroupCode as CustomerGroupCodeValue;
use Larasell\Larasell\Customers\Exceptions\InvalidCustomerGroupCode;

/**
 * Passes what the core accepts as a CustomerGroupCode: lowercase letters and digits joined by - or
 * _, at most 64 characters, e.g. "wholesale-premium".
 */
class CustomerGroupCode implements ValidationRule
{
    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            CustomerGroupCodeValue::of(is_string($value) ? $value : '');
        } catch (InvalidCustomerGroupCode) {
            $fail(__('Use lowercase letters and numbers, joined by - or _.'));
        }
    }
}
