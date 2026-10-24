<?php

namespace Larasell\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Larasell\Admin\Models\Admin;
use Larasell\Admin\Rules\AdminPassword;

/**
 * Editing an admin. Adding one goes through CreateAdmin, which validates on its own.
 */
class AdminRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(Admin::class)->ignore($this->route('admin'))],
            // Left empty, the admin keeps their password.
            'password' => ['nullable', 'string', new AdminPassword],
        ];
    }
}
