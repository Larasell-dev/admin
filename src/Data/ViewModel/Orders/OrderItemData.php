<?php

namespace Larasell\Admin\Data\ViewModel\Orders;

use Larasell\Larasell\Enums\Currency;
use Larasell\Larasell\Models\OrderItem;
use Larasell\Larasell\Price;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** An item as it was ordered, from the order's own copy of the product. */
#[TypeScript]
class OrderItemData extends Data
{
    public function __construct(
        public string $name,
        public ?string $variant,
        public int $quantity,
        public string $unitPrice,
        public string $total,
    ) {}

    public static function fromModel(OrderItem $item, Currency $currency): self
    {
        return new self(
            name: $item->product_name->get(),
            variant: $item->variant_name,
            quantity: $item->quantity,
            unitPrice: Price::format($item->unit_price, $currency, app()->getLocale()),
            total: Price::format($item->total, $currency, app()->getLocale()),
        );
    }
}
