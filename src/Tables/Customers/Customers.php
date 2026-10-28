<?php

namespace Larasell\Admin\Tables\Customers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Larasell\Admin\Enums\Icon;
use Larasell\Admin\Tables\Actions\TableAction;
use Larasell\Admin\Tables\Columns\TextColumn;
use Larasell\Admin\Tables\Table;
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

    public function columns(): array
    {
        return [
            TextColumn::make('name')->map(fn (mixed $value, Customer $customer): string => $customer->fullName()),
            TextColumn::make('orders_count', 'Orders'),
            TextColumn::make('created_at', 'Created')->map(fn (?Carbon $createdAt): ?string => $createdAt?->diffForHumans()),
        ];
    }

    public function rowLink(Customer $customer): string
    {
        return route('larasell.admin.customers.edit', $customer);
    }

    public function headerActions(): array
    {
        return [
            TableAction::make('Add customer')->route('larasell.admin.customers.create')->icon(Icon::Add),
        ];
    }

    public function emptyState(): array
    {
        return [
            'title' => 'No customers yet',
            'description' => 'Customers appear here after they place their first order, or when you add one.',
            'icon' => Icon::Group,
            'actions' => $this->headerActions(),
        ];
    }
}
