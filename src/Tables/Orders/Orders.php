<?php

namespace Larasell\Admin\Tables\Orders;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
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
                ->map(fn (OrderStatus $status): string => match ($status) {
                    OrderStatus::PendingPayment => 'Pending payment',
                    OrderStatus::Paid => 'Paid',
                    OrderStatus::PaymentFailed => 'Payment failed',
                    OrderStatus::Fulfilled => 'Fulfilled',
                    OrderStatus::Cancelled => 'Cancelled',
                })
                ->variant([
                    OrderStatus::PendingPayment->value => Variant::Warning,
                    OrderStatus::Paid->value => Variant::Success,
                    OrderStatus::PaymentFailed->value => Variant::Danger,
                    OrderStatus::Fulfilled->value => Variant::Info,
                ]),
            TextColumn::make('total')->map(fn (Price $total, Order $order): string => Price::format($total, $order->currency, app()->getLocale())),
            TextColumn::make('created_at', 'Date')->map(fn (?Carbon $createdAt): ?string => $createdAt?->diffForHumans()),
        ];
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
