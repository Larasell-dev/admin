<?php

namespace Larasell\Admin\Customer\Tables;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Larasell\Admin\Customer\Actions\DeleteCustomer;
use Larasell\Admin\Platform\InterfaceLocale\Translate;
use Larasell\Admin\Platform\Panel\Icon;
use Larasell\Admin\Platform\Tables\Actions\Action;
use Larasell\Admin\Platform\Tables\Actions\TableAction;
use Larasell\Admin\Platform\Tables\Columns\TextColumn;
use Larasell\Admin\Platform\Tables\Table;
use Larasell\Larasell\Models\Customer;

class Customers extends Table
{
    /**
     * @return Builder<Customer>
     */
    protected function model(): Builder
    {
        return Customer::query()->withCount('orders')->latest();
    }

    public function title(): string
    {
        return __('Customers');
    }

    public function columns(): array
    {
        return [
            TextColumn::make('name', __('Name'))->map(fn (mixed $value, Customer $customer): string => $customer->fullName()),
            TextColumn::make('orders_count', __('Orders')),
            TextColumn::make('created_at', __('Created'))->map(fn (?CarbonInterface $createdAt): ?string => $createdAt?->diffForHumans()),
        ];
    }

    public function rowLink(Customer $customer): string
    {
        return route('larasell.admin.customers.edit', $customer);
    }

    public function headerActions(): array
    {
        return [
            TableAction::make(Translate::later('Add customer'))->route('larasell.admin.customers.create')->icon(Icon::Add),
        ];
    }

    public function actions(): array
    {
        return [
            Action::make(Translate::later('Delete'), handle: fn (Customer $customer) => app(DeleteCustomer::class)->handle($customer))
                ->confirm(
                    title: __('Delete customer'),
                    message: __('Their orders are kept, with the name and email they were placed with. This can\'t be undone.'),
                )
                ->icon(Icon::DeleteBin)
                ->destructive(),
        ];
    }

    public function emptyState(): array
    {
        return [
            'title' => __('No customers yet'),
            'description' => __('Customers appear here after they place their first order, or when you add one.'),
            'icon' => Icon::Group,
            'actions' => $this->headerActions(),
        ];
    }
}
