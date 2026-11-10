<?php

namespace Larasell\Admin\Customer\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class CompanyProfileFormData extends Data
{
    public function __construct(
        public string $legalName,
        public ?string $legalForm,
        public ?string $companyNumber,
        public ?string $vatId,
        public string $countryCode,
        public ?string $industry,
        public ?string $website,
    ) {}
}
