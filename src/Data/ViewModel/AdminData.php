<?php

namespace Larasell\Admin\Data\ViewModel;

use Larasell\Admin\Models\Admin;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class AdminData extends Data
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $avatarUrl,
    ) {}

    public static function fromModel(Admin $admin): self
    {
        return new self(
            name: $admin->name,
            email: $admin->email,
            avatarUrl: $admin->avatarUrl(),
        );
    }
}
