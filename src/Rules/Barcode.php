<?php

namespace Larasell\Admin\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use InvalidArgumentException;
use Larasell\Larasell\Barcode as LarasellBarcode;

/**
 * An EAN-8, UPC-A, EAN-13 or GTIN-14 with a valid check digit, as the library's Barcode takes it.
 */
class Barcode implements ValidationRule
{
    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            LarasellBarcode::of(is_string($value) ? $value : '');
        } catch (InvalidArgumentException $exception) {
            // The library already says what is wrong in words.
            $fail($exception->getMessage());
        }
    }
}
