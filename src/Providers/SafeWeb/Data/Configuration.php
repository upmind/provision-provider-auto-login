<?php

declare(strict_types=1);

namespace Upmind\ProvisionProviders\AutoLogin\Providers\SafeWeb\Data;

use Upmind\ProvisionBase\Provider\DataSet\DataSet;
use Upmind\ProvisionBase\Provider\DataSet\Rules;

/**
 * @property-read string $partner_id Partner identifier
 * @property-read string $api_key Secret API key
 * @property-read string $currency_code Billing currency code (AUD, GBP, USD)
 * @property-read bool|null $sandbox Use the staging environment for development and testing.
*/
class Configuration extends DataSet
{
    public static function rules(): Rules
    {
        return new Rules([
            'partner_id' => ['required', 'string'],
            'api_key' => ['required', 'string'],
            'currency_code' => ['required', 'string', 'in:AUD,GBP,USD'],
            'sandbox' => ['nullable', 'boolean'],
        ]);
    }
}
