<?php

namespace Larasell\Admin\Customer\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\Customer\Data\CreateCustomerPageData;
use Larasell\Admin\Customer\Data\CustomerAccountData;
use Larasell\Admin\Customer\Data\CustomerAddressData;
use Larasell\Admin\Customer\Data\CustomerContactData;
use Larasell\Admin\Customer\Data\CustomerDetailData;
use Larasell\Admin\Customer\Data\CustomerGroupMembershipData;
use Larasell\Admin\Customer\Data\CustomerGroupOptionData;
use Larasell\Admin\Customer\Data\CustomerOptionData;
use Larasell\Admin\Customer\Data\EditCustomerPageData;
use Larasell\Admin\Customer\Http\Requests\CustomerProfileRequest;
use Larasell\Admin\Platform\Audit\CurrentOrigin;
use Larasell\Larasell\Audit\Origin;
use Larasell\Larasell\Customers\Actions\CreateCustomer;
use Larasell\Larasell\Customers\Actions\CreateCustomerPayload;
use Larasell\Larasell\Customers\Actions\UpdateCustomer;
use Larasell\Larasell\Customers\Actions\UpdateCustomerPayload;
use Larasell\Larasell\Customers\CustomerId;
use Larasell\Larasell\Customers\Enums\CustomerSource;
use Larasell\Larasell\Customers\Enums\CustomerType;
use Larasell\Larasell\Customers\Models\Customer;
use Larasell\Larasell\Customers\Models\CustomerAccount;
use Larasell\Larasell\Customers\Models\CustomerAddress;
use Larasell\Larasell\Customers\Models\CustomerContact;
use Larasell\Larasell\Customers\Models\CustomerGroup;
use Laravel\Head\Facades\Head;

class CustomerController
{
    public function create(): Response
    {
        return Inertia::render('Customer/Pages/Create', new CreateCustomerPageData(
            storeUrl: route('larasell.admin.customers.store'),
            indexUrl: route('larasell.admin.customers.index'),
            locales: CustomerOptionData::locales(),
            currencies: CustomerOptionData::currencies(),
            countries: CustomerOptionData::countries(),
        ));
    }

    public function store(CustomerProfileRequest $request, CreateCustomer $create, #[CurrentOrigin] Origin $origin): RedirectResponse
    {
        $customer = $create->handle(new CreateCustomerPayload(
            profile: $request->profile(),
            source: CustomerSource::Admin,
            preferredLocale: $request->preferredLocale(),
            preferredCurrency: $request->preferredCurrency(),
        ), $origin);

        return redirect()->route('larasell.admin.customers.show', $customer);
    }

    public function show(Customer $customer): Response
    {
        $customer->load(['personProfile', 'companyProfile', 'addresses', 'contacts', 'accounts.account', 'accounts.contact', 'groups']);
        Head::title($customer->displayName());

        return Inertia::render('Customer/Pages/Edit', new EditCustomerPageData(
            customer: CustomerDetailData::fromModel($customer),
            updateUrl: route('larasell.admin.customers.update', $customer),
            indexUrl: route('larasell.admin.customers.index'),
            statusUrl: route('larasell.admin.customers.status', $customer),
            addresses: $customer->addresses->sortBy('id')->map(fn (CustomerAddress $address) => CustomerAddressData::fromModel($address))->values()->all(),
            addAddressUrl: route('larasell.admin.customers.addresses.store', $customer),
            contacts: $customer->type === CustomerType::Company
                ? $customer->contacts->sortBy('id')->map(fn (CustomerContact $contact) => CustomerContactData::fromModel($contact))->values()->all()
                : null,
            addContactUrl: $customer->type === CustomerType::Company ? route('larasell.admin.customers.contacts.store', $customer) : null,
            accounts: $customer->accounts->map(fn (CustomerAccount $account) => CustomerAccountData::fromModel($account))->values()->all(),
            groups: $customer->groups->sortBy('code')->map(fn (CustomerGroup $group) => CustomerGroupMembershipData::fromGroup($customer->id, $group))->values()->all(),
            availableGroups: CustomerGroupOptionData::options(),
            addGroupUrl: route('larasell.admin.customers.groups.store', $customer),
            createGroupUrl: route('larasell.admin.customer-groups.create'),
            locales: CustomerOptionData::locales(),
            currencies: CustomerOptionData::currencies(),
            countries: CustomerOptionData::countries(),
        ));
    }

    public function update(CustomerProfileRequest $request, Customer $customer, UpdateCustomer $update, #[CurrentOrigin] Origin $origin): RedirectResponse
    {
        $update->handle(CustomerId::of($customer), new UpdateCustomerPayload(
            profile: $request->profile(),
            preferredLocale: $request->preferredLocale(),
            preferredCurrency: $request->preferredCurrency(),
        ), $origin);

        return redirect()->route('larasell.admin.customers.show', $customer);
    }
}
