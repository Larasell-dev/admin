<?php

namespace Larasell\Admin\Customer\Data;

use Larasell\Larasell\Customers\Models\CustomerContact;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class CustomerContactData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $firstName,
        public string $lastName,
        public ?string $email,
        public ?string $phone,
        public ?string $jobTitle,
        public string $updateUrl,
        public string $destroyUrl,
    ) {}

    public static function fromModel(CustomerContact $contact): self
    {
        $route = [$contact->customer_id, $contact];

        return new self(
            id: $contact->id,
            name: trim($contact->first_name.' '.$contact->last_name),
            firstName: $contact->first_name,
            lastName: $contact->last_name,
            email: $contact->email,
            phone: $contact->phone,
            jobTitle: $contact->job_title,
            updateUrl: route('larasell.admin.customers.contacts.update', $route),
            destroyUrl: route('larasell.admin.customers.contacts.destroy', $route),
        );
    }
}
