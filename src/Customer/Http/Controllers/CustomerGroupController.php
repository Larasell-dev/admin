<?php

namespace Larasell\Admin\Customer\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Larasell\Admin\Customer\Actions\DeleteCustomerGroup;
use Larasell\Admin\Customer\AuditRecords\CustomerGroupCreated;
use Larasell\Admin\Customer\AuditRecords\CustomerGroupUpdated;
use Larasell\Admin\Customer\Data\CustomerGroupData;
use Larasell\Admin\Customer\Data\CustomerGroupPageData;
use Larasell\Admin\Customer\Http\Requests\CustomerGroupRequest;
use Larasell\Admin\Customer\Http\Requests\DeleteCustomerGroupRequest;
use Larasell\Admin\Platform\Audit\Auditor;
use Larasell\Larasell\Customers\Actions\CreateCustomerGroup;
use Larasell\Larasell\Customers\Actions\UpdateCustomerGroup;
use Larasell\Larasell\Customers\CustomerGroupId;
use Larasell\Larasell\Customers\Models\CustomerGroup;
use Laravel\Head\Facades\Head;

class CustomerGroupController
{
    public function create(): Response
    {
        return Inertia::render('Customer/Pages/EditGroup', new CustomerGroupPageData(null, route('larasell.admin.customer-groups.store'), null, route('larasell.admin.customer-groups.index')));
    }

    public function store(CustomerGroupRequest $request, CreateCustomerGroup $create, Auditor $auditor): RedirectResponse
    {
        $group = DB::transaction(function () use ($request, $create, $auditor): CustomerGroup {
            $group = $create->handle($request->payload());
            $auditor->record(new CustomerGroupCreated($group->id, ['code' => $group->code]));

            return $group;
        });

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

    public function update(CustomerGroupRequest $request, CustomerGroup $group, UpdateCustomerGroup $update, Auditor $auditor): RedirectResponse
    {
        DB::transaction(function () use ($request, $group, $update, $auditor): void {
            $update->handle(CustomerGroupId::of($group), $request->payload($group));
            $auditor->record(new CustomerGroupUpdated($group->id, ['code' => $request->string('code')->value()]));
        });

        return redirect()->route('larasell.admin.customer-groups.edit', $group);
    }

    public function destroy(DeleteCustomerGroupRequest $request, CustomerGroup $group, DeleteCustomerGroup $delete): RedirectResponse
    {
        $delete->handle($group);

        return redirect()->route('larasell.admin.customer-groups.index');
    }
}
