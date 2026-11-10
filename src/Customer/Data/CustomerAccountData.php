<?php

namespace Larasell\Admin\Customer\Data;

use Larasell\Larasell\Customers\Models\CustomerAccount;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** One of the app's users who acts for the customer. The admin only shows and unlinks it. */
#[TypeScript]
class CustomerAccountData extends Data
{
    public function __construct(
        public int $id,
        /** The user's email or name, or its type and key when it has neither. */
        public string $label,
        /** The contact the user is, if any. */
        public ?string $contact,
        public string $destroyUrl,
    ) {}

    public static function fromModel(CustomerAccount $account): self
    {
        $user = $account->account;
        $label = $user?->getAttribute('email') ?? $user?->getAttribute('name') ?? class_basename($account->account_type).' #'.$account->account_id;
        $contact = $account->contact;

        return new self(
            id: $account->id,
            label: (string) $label,
            contact: $contact === null ? null : trim($contact->first_name.' '.$contact->last_name),
            destroyUrl: route('larasell.admin.customers.accounts.destroy', [$account->customer_id, $account]),
        );
    }
}
