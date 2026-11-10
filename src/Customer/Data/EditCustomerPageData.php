<?php

namespace Larasell\Admin\Customer\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class EditCustomerPageData extends Data
{
    public function __construct(
        public CustomerDetailData $customer,
        public string $updateUrl,
        public string $indexUrl,
        public string $statusUrl,
        /** @var array<int, CustomerAddressData> */
        public array $addresses,
        public string $addAddressUrl,
        /**
         * Null for individuals, who have no contacts.
         *
         * @var array<int, CustomerContactData>|null
         */
        public ?array $contacts,
        public ?string $addContactUrl,
        /** @var array<int, CustomerAccountData> */
        public array $accounts,
        /** @var array<int, CustomerGroupMembershipData> */
        public array $groups,
        /** @var array<int, CustomerGroupOptionData> */
        public array $availableGroups,
        public string $addGroupUrl,
        public string $createGroupUrl,
        /** @var array<int, CustomerOptionData> */
        public array $locales,
        /** @var array<int, CustomerOptionData> */
        public array $currencies,
        /** @var array<int, CustomerOptionData> */
        public array $countries,
    ) {}
}
