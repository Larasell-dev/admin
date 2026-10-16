<?php

namespace Larasell\Admin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Larasell\Admin\Http\Controllers\AvatarController;
use Larasell\Admin\Models\Admin;

class ProfileRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(Admin::class)->ignore($this->user('larasell-admin')),
            ],
            // Avatars are uploaded separately; only this session's upload can be assigned.
            'avatar' => ['nullable', 'string', Rule::in([$this->session()->get(AvatarController::PENDING_SESSION_KEY)])],
            'remove_avatar' => ['boolean'],
        ];
    }
}
