<?php

namespace App\Support;

/**
 * Single source of truth for the Pro price shown in the UI.
 * Driven by config('services.stripe.pro_monthly_usd') (env STRIPE_PRO_MONTHLY_USD),
 * which must match the live Stripe price. Never hardcode the price in views.
 */
class Pricing
{
    public static function proMonthlyAmount(): float
    {
        return (float) config('services.stripe.pro_monthly_usd', 5);
    }

    /** "$5" or "$4.99" */
    public static function proMonthly(): string
    {
        $amount = self::proMonthlyAmount();

        return '$' . (floor($amount) == $amount ? number_format($amount, 0) : number_format($amount, 2));
    }
}
