<?php

declare(strict_types=1);

namespace Upmind\ProvisionProviders\AutoLogin\Data;

use Upmind\ProvisionBase\Provider\DataSet\DataSet;
use Upmind\ProvisionBase\Provider\DataSet\Rules;

/**
 * Billing metadata.
 *
 * @property-read string|null $transaction_id Unique transaction ID
 * @property-read string|null $invoice_number Order invoice number
 * @property-read string|float $amount Amount charged
 * @property-read string $currency Currency code (ISO 4217)
 * @property-read string|float|null $discount Discount amount
 * @property-read string|float|null $tax Tax amount
 * @property-read int|null $billing_cycle_months Number of months in the billing cycle
 * @property-read string|null $expiry_date Expiry date in YYYY-MM-DD format
 */
class BillingParams extends DataSet
{
    public static function rules(): Rules
    {
        return new Rules([
            'transaction_id' => ['nullable', 'string'],
            'invoice_number' => ['nullable', 'string'],
            'amount' => ['required', 'numeric'],
            'currency' => ['required', 'string'], // ISO 4217
            'discount' => ['nullable', 'numeric'],
            'tax' => ['nullable', 'numeric',],
            'billing_cycle_months' => ['nullable', 'integer'],
            'expiry_date' => ['nullable', 'date:Y-m-d'],
        ]);
    }
}
