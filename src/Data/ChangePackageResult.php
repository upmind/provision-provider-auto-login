<?php

declare(strict_types=1);

namespace Upmind\ProvisionProviders\AutoLogin\Data;

use Upmind\ProvisionBase\Provider\DataSet\ResultData;
use Upmind\ProvisionBase\Provider\DataSet\Rules;

/**
 * @property-read mixed $username Username or other unique service identifier
 * @property-read string|null $service_identifier Secondary service identifier, if any
 * @property-read string|null $package_identifier Package identifier, if any
 * @property-read array|null $extra Extra data, if any
 */
class ChangePackageResult extends ResultData
{
    public static function rules(): Rules
    {
        return new Rules([
            'username' => ['filled'],
            'service_identifier' => ['nullable', 'string'],
            'package_identifier' => ['nullable', 'string'],
            'extra' => ['nullable', 'array'],
        ]);
    }

    /**
     * Set the result username.
     */
    public function setUsername(string $username): self
    {
        $this->setValue('username', $username);
        return $this;
    }

    /**
     * Set the result secondary service identifier.
     */
    public function setServiceIdentifier(?string $serviceIdentifier): self
    {
        $this->setValue('service_identifier', $serviceIdentifier);
        return $this;
    }

    /**
     * Set the result package identifier.
     */
    public function setPackageIdentifier(?string $packageIdentifier): self
    {
        $this->setValue('package_identifier', $packageIdentifier);
        return $this;
    }

    /**
     * Set the result extra data.
     */
    public function setExtra(?array $extra): self
    {
        $this->setValue('extra', $extra);
        return $this;
    }
}