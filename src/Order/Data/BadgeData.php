<?php

namespace Larasell\Admin\Order\Data;

use Larasell\Admin\Platform\Tables\Enums\Variant;
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
            OrderStatus::PendingPayment => new self(__('Pending payment'), Variant::Warning),
            OrderStatus::Paid => new self(__('Paid'), Variant::Success),
            OrderStatus::PaymentFailed => new self(__('Payment failed'), Variant::Danger),
            OrderStatus::Fulfilled => new self(__('Fulfilled'), Variant::Info),
            OrderStatus::Cancelled => new self(__('Cancelled'), Variant::Neutral),
        };
    }

    public static function fromPaymentStatus(PaymentStatus $status): self
    {
        return match ($status) {
            PaymentStatus::Pending => new self(__('Pending'), Variant::Warning),
            PaymentStatus::Succeeded => new self(__('Succeeded'), Variant::Success),
            PaymentStatus::Failed => new self(__('Failed'), Variant::Danger),
            PaymentStatus::Cancelled => new self(__('Cancelled'), Variant::Neutral),
        };
    }
}
