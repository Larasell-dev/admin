<?php

namespace Larasell\Admin\Customer\Tables;

use Illuminate\Database\Eloquent\Builder;
use Larasell\Admin\Customer\Actions\DeleteCustomerGroup;
use Larasell\Admin\Platform\Audit\Origins;
use Larasell\Admin\Platform\InterfaceLocale\Translate;
use Larasell\Admin\Platform\Panel\Icon;
use Larasell\Admin\Platform\Tables\Actions\Action;
use Larasell\Admin\Platform\Tables\Actions\TableAction;
use Larasell\Admin\Platform\Tables\Columns\TextColumn;
use Larasell\Admin\Platform\Tables\Table;
use Larasell\Larasell\Customers\CustomerGroupId;
use Larasell\Larasell\Customers\Models\CustomerGroup;
use Larasell\Larasell\Translatable;

class CustomerGroups extends Table
{
    /**
     * @return Builder<CustomerGroup>
     */
    protected function model(): Builder
    {
        return CustomerGroup::query()->withCount('customers')->orderBy('code');
    }

    public function title(): string
    {
        return __('Customer groups');
    }

    public function columns(): array
    {
        return [
            TextColumn::make('name', __('Name'))->map(fn (Translatable $name): string => $name->get(app()->getLocale())),
            TextColumn::make('code', __('Code')),
            TextColumn::make('customers_count', __('Customers')),
        ];
    }

    public function rowLink(CustomerGroup $group): string
    {
        return route('larasell.admin.customer-groups.edit', $group);
    }

    public function actions(): array
    {
        return [
            Action::make(Translate::later('Delete'), handle: fn (CustomerGroup $group) => app(DeleteCustomerGroup::class)->handle(CustomerGroupId::of($group), app(Origins::class)->current()))
                ->confirm(
                    title: __('Delete customer group'),
                    message: __('Rules that refer to the group\'s code stop applying. Only groups without customers can be deleted. This can\'t be undone.'),
                    confirmationText: fn (CustomerGroup $group): string => $group->code,
                )
                ->icon(Icon::DeleteBin)
                ->destructive(),
        ];
    }

    public function headerActions(): array
    {
        return [
            TableAction::make(Translate::later('Add customer group'))->route('larasell.admin.customer-groups.create')->icon(Icon::Add),
        ];
    }

    public function emptyState(): array
    {
        return [
            'title' => __('No customer groups yet'),
            'description' => __('Groups such as Retail or Wholesale sort customers for prices and rules.'),
            'icon' => Icon::Group,
            'actions' => $this->headerActions(),
        ];
    }
}
