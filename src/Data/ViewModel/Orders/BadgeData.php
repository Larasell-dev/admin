<?php

namespace Larasell\Admin\Data\ViewModel\Orders;

use Larasell\Admin\Enums\Table\Variant;
use Larasell\Larasell\Enums\OrderStatus;
use Larasell\Larasell\Enums\PaymentStatus;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/** A status as the page shows it: its text and the colour of its badge. */
#[TypeScript]
class BadgeData extends Data
{
    public function __construct(
        public string $label,
        #[LiteralTypeScriptType('import("@larasell-dev/ui").BadgeVariant')]
        public Variant $variant,
    ) {}

    public static function fromOrderStatus(OrderStatus $status): self
    {
        return match ($status) {
            OrderStatus::PendingPayment => new self('Pending payment', Variant::Warning),
            OrderStatus::Paid => new self('Paid', Variant::Success),
            OrderStatus::PaymentFailed => new self('Payment failed', Variant::Danger),
            OrderStatus::Fulfilled => new self('Fulfilled', Variant::Info),
            OrderStatus::Cancelled => new self('Cancelled', Variant::Neutral),
        };
    }

    public static function fromPaymentStatus(PaymentStatus $status): self
    {
        return match ($status) {
            PaymentStatus::Pending => new self('Pending', Variant::Warning),
            PaymentStatus::Succeeded => new self('Succeeded', Variant::Success),
            PaymentStatus::Failed => new self('Failed', Variant::Danger),
            PaymentStatus::Cancelled => new self('Cancelled', Variant::Neutral),
        };
    }
}
