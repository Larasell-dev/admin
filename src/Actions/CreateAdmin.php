<?php

namespace Larasell\Admin\Actions;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Larasell\Admin\Models\Admin;

class CreateAdmin
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function handle(array $input): Admin
    {
        $validated = Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(Admin::class)],
            'password' => ['required', 'string', $this->passwordRule()],
        ], [
            'email.unique' => 'An admin with the email [:input] already exists.',
        ])->validate();

        return Admin::query()->create($validated);
    }

    private function passwordRule(): Password
    {
        return Password::min(12)
            ->mixedCase()
            ->numbers()
            ->symbols()
            ->when(App::isProduction(), fn (Password $rule) => $rule->uncompromised());
    }
}
