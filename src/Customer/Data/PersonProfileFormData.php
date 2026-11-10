<?php

namespace Larasell\Admin\Customer\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class PersonProfileFormData extends Data
{
    public function __construct(
        public ?string $salutation,
        public string $firstName,
        public string $lastName,
        public ?string $email,
        public ?string $phone,
    ) {}
}
