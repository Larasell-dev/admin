<?php

namespace Larasell\Admin\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Storage;

class Admin extends Authenticatable
{
    protected $table = 'larasell_admin_users';

    protected $fillable = [
        'name',
        'email',
        'avatar_path',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function avatarUrl(): ?string
    {
        if ($this->avatar_path === null) {
            return null;
        }

        return Storage::disk(config('larasell-admin.disk'))->url($this->avatar_path);
    }
}
