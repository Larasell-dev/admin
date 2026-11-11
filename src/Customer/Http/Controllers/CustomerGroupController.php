<?php

namespace Larasell\Admin\Customer\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\Customer\Actions\DeleteCustomerGroup;
use Larasell\Admin\Customer\Data\CustomerGroupData;
use Larasell\Admin\Customer\Data\CustomerGroupPageData;
use Larasell\Admin\Customer\Http\Requests\CustomerGroupRequest;
use Larasell\Admin\Customer\Http\Requests\DeleteCustomerGroupRequest;
use Larasell\Admin\Platform\Audit\CurrentOrigin;
use Larasell\Larasell\Audit\Origin;
use Larasell\Larasell\Customers\Actions\CreateCustomerGroup;
use Larasell\Larasell\Customers\Actions\UpdateCustomerGroup;
use Larasell\Larasell\Customers\CustomerGroupId;
use Larasell\Larasell\Customers\Models\CustomerGroup;
use Laravel\Head\Facades\Head;

class CustomerGroupController
{
    public function create(): Response
    {
        return Inertia::render('Customer/Pages/EditGroup', new CustomerGroupPageData(
            group: null,
            saveUrl: route('larasell.admin.customer-groups.store'),
            destroyUrl: null,
            indexUrl: route('larasell.admin.customer-groups.index'),
        ));
    }

    public function store(CustomerGroupRequest $request, CreateCustomerGroup $create, #[CurrentOrigin] Origin $origin): RedirectResponse
    {
        $group = $create->handle($request->payload(), $origin);

        return redirect()->route('larasell.admin.customer-groups.edit', $group);
    }

    public function edit(CustomerGroup $group): Response
    {
        $data = CustomerGroupData::fromModel($group);
        Head::title($data->name);

        return Inertia::render('Customer/Pages/EditGroup', new CustomerGroupPageData(
            $data,
            route('larasell.admin.customer-groups.update', $group),
            route('larasell.admin.customer-groups.destroy', $group),
            route('larasell.admin.customer-groups.index'),
        ));
    }

    public function update(CustomerGroupRequest $request, CustomerGroup $group, UpdateCustomerGroup $update, #[CurrentOrigin] Origin $origin): RedirectResponse
    {
        $update->handle(CustomerGroupId::of($group), $request->payload($group), $origin);

        return redirect()->route('larasell.admin.customer-groups.edit', $group);
    }

    public function destroy(DeleteCustomerGroupRequest $request, CustomerGroup $group, DeleteCustomerGroup $delete, #[CurrentOrigin] Origin $origin): RedirectResponse
    {
        $delete->handle(CustomerGroupId::of($group), $origin);

        return redirect()->route('larasell.admin.customer-groups.index');
    }
}
