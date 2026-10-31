<?php

namespace Larasell\Admin\Enums;

use Illuminate\Support\Carbon;

/**
 * How long a new API token stays valid: until the end of the day the period ends.
 *
 * @internal The choices of the new API token form.
 */
enum ApiTokenExpiry: string
{
    case OneWeek = '1_week';
    case OneMonth = '1_month';
    case ThreeMonths = '3_months';
    case SixMonths = '6_months';
    case NineMonths = '9_months';

    public function label(): string
    {
        return match ($this) {
            self::OneWeek => 'After 1 week',
            self::OneMonth => 'After 1 month',
            self::ThreeMonths => 'After 3 months',
            self::SixMonths => 'After 6 months',
            self::NineMonths => 'After 9 months',
        };
    }

    public function expiresAt(): Carbon
    {
        // The app's own time zone, passed explicitly: "end of the day" means the shop's day.
        $today = Carbon::today(config('app.timezone'));

        // NoOverflow: a month from January 31 is February 28, not March 3.
        return (match ($this) {
            self::OneWeek => $today->addWeek(),
            self::OneMonth => $today->addMonthsNoOverflow(1),
            self::ThreeMonths => $today->addMonthsNoOverflow(3),
            self::SixMonths => $today->addMonthsNoOverflow(6),
            self::NineMonths => $today->addMonthsNoOverflow(9),
        })->endOfDay();
    }
}
