<?php

namespace Larasell\Admin\Data\ViewModel\Orders;

use Larasell\Larasell\Enums\TaxPriceMode;
use Larasell\Larasell\Models\Order;
use Larasell\Larasell\Price;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** An order as placed, formatted to read. The page shows it as is; orders aren't edited. */
#[TypeScript]
class OrderDetailData extends Data
{
    public function __construct(
        public string $number,
        public BadgeData $status,
        public string $placedAt,
        /** @var OrderItemData[] */
        public array $items,
        public string $subtotal,
        /** Null when the order has no discount. */
        public ?string $discount,
        /** The shipping option's name and price, or null for an order without shipping. */
        public ?AmountData $shipping,
        /** @var AmountData[] One per tax rate, a single "Tax" without a breakdown, or none. */
        public array $taxes,
        /** Whether the prices already include the taxes, rather than adding them on top. */
        public bool $taxIncluded,
        public string $total,
        public string $customerName,
        public string $customerEmail,
        public ?string $customerPhone,
        public ?AddressData $billingAddress,
        public ?AddressData $shippingAddress,
        /** @var PaymentData[] Newest first. */
        public array $payments,
    ) {}

    public static function fromModel(Order $order): self
    {
        $format = fn (Price $price): string => Price::format($price, $order->currency, app()->getLocale());
        // Read raw first: the cast throws instead of returning null for an order without shipping.
        $shippingPrice = $order->getRawOriginal('shipping_price') === null ? null : $order->shipping_price;

        return new self(
            number: $order->number,
            status: BadgeData::fromOrderStatus($order->status),
            placedAt: $order->created_at?->format('M j, Y') ?? '',
            items: $order->items()->orderBy('id')->get()
                ->map(fn ($item) => OrderItemData::fromModel($item, $order->currency))->all(),
            subtotal: $format($order->subtotal),
            discount: $order->discount_total->isZero() ? null : $format($order->discount_total),
            shipping: $shippingPrice === null
                ? null
                : new AmountData($order->shipping_option_name ?? 'Shipping', $format($shippingPrice)),
            taxes: self::taxes($order, $format),
            taxIncluded: $order->tax_price_mode === TaxPriceMode::Inclusive,
            total: $format($order->total),
            customerName: $order->customer_name,
            customerEmail: $order->customer_email,
            customerPhone: $order->customer_phone,
            billingAddress: $order->billing_address === null ? null : AddressData::fromAddress($order->billing_address),
            shippingAddress: $order->shipping_address === null ? null : AddressData::fromAddress($order->shipping_address),
            payments: $order->payments()->latest()->latest('id')->get()
                ->map(fn ($payment) => PaymentData::fromModel($payment, $order->currency))->all(),
        );
    }

    /**
     * The taxes by rate from the order's tax snapshot, or its tax total alone when the
     * snapshot has no breakdown.
     *
     * @param  \Closure(Price): string  $format
     * @return AmountData[]
     */
    private static function taxes(Order $order, \Closure $format): array
    {
        if ($order->tax_total === null) {
            return [];
        }

        $components = $order->tax_snapshot['components'] ?? [];

        if ($components === []) {
            return [new AmountData('Tax', $format($order->tax_total))];
        }

        return array_map(function (array $component) use ($format): AmountData {
            // Snapshots store rates with four decimals, e.g. "19.0000".
            $rate = rtrim(rtrim($component['rate'], '0'), '.');

            return new AmountData("{$component['name']} ({$rate}%)", $format(Price::fromArray($component['amount'])));
        }, $components);
    }
}
