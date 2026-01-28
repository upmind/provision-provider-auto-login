<?php

declare(strict_types=1);

namespace Upmind\ProvisionProviders\AutoLogin\Data;

use Upmind\ProvisionBase\Provider\DataSet\DataSet;
use Upmind\ProvisionBase\Provider\DataSet\Rules;

/**
 * @property-read mixed $username Username or other unique service identifier
 * @property-read string|null $service_identifier Secondary service identifier to use, if known up-front
 * @property-read string|null $package_identifier Service package identifier, if any
 * @property-read mixed[]|null $extra Any extra data to pass to the service endpoint
 */
class ChangePackageParams extends DataSet
{
    public static function rules(): Rules
    {
        return new Rules([
            'username' => ['required'],
            'service_identifier' => ['nullable', 'string'],
            'package_identifier' => ['nullable', 'string'],
            'extra' => ['nullable', 'array'],
        ]);
    }
}