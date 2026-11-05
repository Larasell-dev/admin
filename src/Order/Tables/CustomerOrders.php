<?php

namespace Larasell\Admin\Order\Tables;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Larasell\Admin\Order\Data\BadgeData;
use Larasell\Admin\Platform\Tables\Columns\BadgeColumn;
use Larasell\Admin\Platform\Tables\Columns\TextColumn;
use Larasell\Admin\Platform\Tables\Enums\Variant;
use Larasell\Admin\Platform\Tables\Table;
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
        return __('Orders');
    }

    public function columns(): array
    {
        return [
            TextColumn::make('number', __('Order')),
            BadgeColumn::make('status', __('Status'))
                ->map(fn (OrderStatus $status): string => BadgeData::fromOrderStatus($status)->label)
                ->variant(fn (OrderStatus $status): Variant => BadgeData::fromOrderStatus($status)->variant),
            TextColumn::make('total', __('Total'))->map(fn (Price $total, Order $order): string => Price::format($total, $order->currency, app()->getLocale())),
            TextColumn::make('created_at', __('Date'))->map(fn (?CarbonInterface $createdAt): ?string => $createdAt?->diffForHumans()),
        ];
    }

    public function rowLink(Order $order): ?string
    {
        return config('larasell.admin.orders.enabled') ? route('larasell.admin.orders.show', $order) : null;
    }
}
