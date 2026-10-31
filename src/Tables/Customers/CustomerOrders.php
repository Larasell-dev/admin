<?php

namespace Larasell\Admin\Tables\Customers;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Larasell\Admin\Data\ViewModel\Orders\BadgeData;
use Larasell\Admin\Enums\Table\Variant;
use Larasell\Admin\Tables\Columns\BadgeColumn;
use Larasell\Admin\Tables\Columns\TextColumn;
use Larasell\Admin\Tables\Table;
use Larasell\Larasell\Enums\OrderStatus;
use Larasell\Larasell\Models\Customer;
use Larasell\Larasell\Models\Order;
use Larasell\Larasell\Price;

/**
 * The orders a customer placed, on the customer's page. Separate from the orders module's
 * table, so it works while the orders are turned off; its rows then link nowhere.
 */
class CustomerOrders extends Table
{
    public function __construct(
        public Customer $customer,
    ) {}

    /**
     * @return HasMany<Order, Customer>
     */
    protected function model(): HasMany
    {
        return $this->customer->orders()->latest();
    }

    public function title(): string
    {
        return 'Orders';
    }

    public function columns(): array
    {
        return [
            TextColumn::make('number', 'Order'),
            BadgeColumn::make('status')
                ->map(fn (OrderStatus $status): string => BadgeData::fromOrderStatus($status)->label)
                ->variant(fn (OrderStatus $status): Variant => BadgeData::fromOrderStatus($status)->variant),
            TextColumn::make('total')->map(fn (Price $total, Order $order): string => Price::format($total, $order->currency, app()->getLocale())),
            TextColumn::make('created_at', 'Date')->map(fn (?CarbonInterface $createdAt): ?string => $createdAt?->diffForHumans()),
        ];
    }

    public function rowLink(Order $order): ?string
    {
        return config('larasell.admin.orders.enabled') ? route('larasell.admin.orders.show', $order) : null;
    }
}
