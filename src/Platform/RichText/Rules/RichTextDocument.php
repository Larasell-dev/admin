<?php

namespace Larasell\Admin\Platform\RichText\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Larasell\Larasell\Exceptions\InvalidRichText;
use Larasell\Larasell\RichText\RichText;

/**
 * A rich text document as JSON, as the editor sends it. The editor only writes valid documents,
 * so an invalid one means a stale or tampered page, which reloading fixes.
 */
class RichTextDocument implements ValidationRule
{
    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            RichText::fromJson(is_string($value) ? $value : '');
        } catch (InvalidRichText) {
            $fail(__('The :attribute can\'t be saved. Reload the page and try again.'));
        }
    }
}
