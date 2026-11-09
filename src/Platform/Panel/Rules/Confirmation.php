<?php

namespace Larasell\Admin\Platform\Panel\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * The text an admin types to confirm something that can't be undone, such as a record's name.
 * Spaces around it are ignored. It also fails when nothing was typed.
 */
class Confirmation implements ValidationRule
{
    /** Runs on a missing or empty value too, so leaving the field out doesn't skip it. */
    public bool $implicit = true;

    public function __construct(private readonly string $expected) {}

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) !== $this->expected) {
            $fail(__('Type ":text" to confirm.', ['text' => $this->expected]));
        }
    }
}
