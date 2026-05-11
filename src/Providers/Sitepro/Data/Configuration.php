<?php

declare(strict_types=1);

namespace Upmind\ProvisionProviders\AutoLogin\Providers\Sitepro\Data;

use Upmind\ProvisionBase\Provider\DataSet\DataSet;
use Upmind\ProvisionBase\Provider\DataSet\Rules;

/**
 * Site.pro API credentials and configuration.
 *
 * @property-read string $username Site.pro API username
 * @property-read string $password Site.pro API password
 * @property-read string $api_url Site.pro API URL
 * @property-read string $brand_id Site.pro Brand ID
 *
 * Publication type
 * @property-read string $publish_type Publication type. One of: external, ssh
 */
class Configuration extends DataSet
{
    public static function rules(): Rules
    {
        return new Rules([
            'username' => ['required', 'string', 'min:3'],
            'password' => ['required', 'string', 'min:6'],
            'api_url' => ['required', 'string'],
            'brand_id' => ['required', 'string'],
            'publish_type' => ['required', 'string', 'in:external,ssh'],
        ]);
    }
}
