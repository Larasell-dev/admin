<?php

namespace Larasell\Admin\Data\ViewModel\Orders;

use Larasell\Larasell\Enums\Currency;
use Larasell\Larasell\Models\Payment;
use Larasell\Larasell\Price;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class PaymentData extends Data
{
    public function __construct(
        public string $method,
        public BadgeData $status,
        public ?string $reference,
        public string $amount,
    ) {}

    public static function fromModel(Payment $payment, Currency $currency): self
    {
        return new self(
            method: $payment->method,
            status: BadgeData::fromPaymentStatus($payment->status),
            reference: $payment->reference,
            amount: Price::format($payment->amount, $currency, app()->getLocale()),
        );
    }
}
