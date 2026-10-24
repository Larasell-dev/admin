<?php

namespace Larasell\Admin\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Validator;
use Illuminate\Translation\PotentiallyTranslatedString;
use Illuminate\Validation\Rules\Password;

class AdminPassword implements ValidationRule
{
    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $password = Password::min(12)
            ->mixedCase()
            ->numbers()
            ->symbols()
            ->when(App::isProduction(), fn (Password $rule) => $rule->uncompromised());

        $errors = Validator::make([$attribute => $value], [$attribute => [$password]])->errors();

        foreach ($errors->all() as $message) {
            $fail($message);
        }
    }
}
