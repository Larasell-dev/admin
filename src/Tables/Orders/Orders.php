<?php

namespace Larasell\Admin\Tables\Orders;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Larasell\Admin\Data\ViewModel\Orders\BadgeData;
use Larasell\Admin\Enums\Icon;
use Larasell\Admin\Enums\Table\Variant;
use Larasell\Admin\Tables\Columns\BadgeColumn;
use Larasell\Admin\Tables\Columns\TextColumn;
use Larasell\Admin\Tables\Table;
use Larasell\Larasell\Enums\OrderStatus;
use Larasell\Larasell\Models\Order;
use Larasell\Larasell\Price;

class Orders extends Table
{
    /**
     * @return Builder<Order>
     */
    protected function model(): Builder
    {
        return Order::query()->latest();
    }

    public function columns(): array
    {
        return [
            TextColumn::make('number', 'Order'),
            TextColumn::make('customer_name', 'Customer'),
            BadgeColumn::make('status')
                ->map(fn (OrderStatus $status): string => BadgeData::fromOrderStatus($status)->label)
                ->variant(fn (OrderStatus $status): Variant => BadgeData::fromOrderStatus($status)->variant),
            TextColumn::make('total')->map(fn (Price $total, Order $order): string => Price::format($total, $order->currency, app()->getLocale())),
            TextColumn::make('created_at', 'Date')->map(fn (?Carbon $createdAt): ?string => $createdAt?->diffForHumans()),
        ];
    }

    public function rowLink(Order $order): string
    {
        return route('larasell.admin.orders.show', $order);
    }

    public function emptyState(): array
    {
        return [
            'title' => 'No orders yet',
            'description' => 'Orders appear here once customers check out.',
            'icon' => Icon::Receipt,
        ];
    }
}
