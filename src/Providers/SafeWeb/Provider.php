<?php

declare(strict_types=1);

namespace Upmind\ProvisionProviders\AutoLogin\Providers\SafeWeb;

use DateTime;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Upmind\ProvisionBase\Provider\Contract\ProviderInterface;
use Upmind\ProvisionBase\Provider\DataSet\AboutData;
use Upmind\ProvisionProviders\AutoLogin\Category;
use Upmind\ProvisionProviders\AutoLogin\Data\AccountIdentifierParams;
use Upmind\ProvisionProviders\AutoLogin\Data\ChangePackageParams;
use Upmind\ProvisionProviders\AutoLogin\Data\ChangePackageResult;
use Upmind\ProvisionProviders\AutoLogin\Data\CreateParams;
use Upmind\ProvisionProviders\AutoLogin\Data\CreateResult;
use Upmind\ProvisionProviders\AutoLogin\Data\EmptyResult;
use Upmind\ProvisionProviders\AutoLogin\Data\LoginResult;
use Upmind\ProvisionProviders\AutoLogin\Providers\SafeWeb\Data\Configuration;
use Upmind\ProvisionProviders\AutoLogin\Providers\SafeWeb\ResponseHandlers\ResponseHandler;

class Provider extends Category implements ProviderInterface
{
    protected Configuration $configuration;

    public static function aboutProvider(): AboutData
    {
        return AboutData::create()
            ->setName('SafeWeb')
            ->setDescription('Manage SafeWeb dark web monitoring accounts.')
            ->setLogoUrl('https://api.upmind.io/images/logos/provision/safeweb-logo_2x.png');
    }

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Upmind\ProvisionBase\Exception\ProvisionFunctionError
     * @throws \Upmind\ProvisionProviders\AutoLogin\Exceptions\CannotParseResponse
     */
    public function create(CreateParams $params): CreateResult
    {
        if (empty($params->email)) {
            $this->errorResult('Customer email is required');
        }

        $customerReference = (string)$params->user_id;
        $companyName = $params->customer_name ?? $customerReference;
        $email = $params->email;
        $planType = $params->package_identifier ?? 'safeweb-basic';

        $billedFromDate = (new DateTime('+1 month', new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.v\Z');

        $body = [
            'companyName' => $companyName,
            'contactEmail' => $email,
            'customerReference' => $customerReference,
            'alertRecipients' => [$email],
            'price' => $params->extra['price'] ?? 0,
            'billedFromDate' => $billedFromDate,
            'currencyCode' => $this->configuration->currency_code,
            'planType' => $planType,
            'platformAccess' => true,
        ];

        if (!empty($params->extra['assetsDomains'])) {
            $body['assetsDomains'] = (array)$params->extra['assetsDomains'];
        }

        if (!empty($params->extra['assetsEmails'])) {
            $body['assetsEmails'] = (array)$params->extra['assetsEmails'];
        }

        if (empty($body['assetsDomains']) && empty($body['assetsEmails'])) {
            $body['assetsEmails'] = [$email];
        }

        $response = $this->client()->post('/api/integrations/customer/onboard', [
            RequestOptions::JSON => $body,
        ]);

        $handler = new ResponseHandler($response);
        $handler->assertSuccess();

        $customerId = $handler->getData('customerId');

        return CreateResult::create()
            ->setUsername($customerReference)
            ->setServiceIdentifier($customerId)
            ->setPackageIdentifier($planType);
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Upmind\ProvisionBase\Exception\ProvisionFunctionError
     * @throws \Upmind\ProvisionProviders\AutoLogin\Exceptions\CannotParseResponse
     */
    public function login(AccountIdentifierParams $params): LoginResult
    {
        $customerId = $params->service_identifier ?: $params->username;

        $response = $this->client()->post("/api/integrations/customer/{$customerId}/magic-link");

        $handler = new ResponseHandler($response);
        $handler->assertSuccess();

        $loginUrl = $handler->getData('url');

        return LoginResult::create()->setUrl($loginUrl);
    }

    /**
     * @throws \Upmind\ProvisionBase\Exception\ProvisionFunctionError
     */
    public function suspend(AccountIdentifierParams $params): EmptyResult
    {
        $customerId = $params->service_identifier ?: $params->username;

        $response = $this->client()->post('/api/integrations/customer/offboard', [
            RequestOptions::JSON => [
                'customerId' => $customerId,
            ],
        ]);

        $handler = new ResponseHandler($response);
        $handler->assertSuccess();

        return EmptyResult::create();
    }

    /**
     * @throws \Upmind\ProvisionBase\Exception\ProvisionFunctionError
     */
    public function unsuspend(AccountIdentifierParams $params): EmptyResult
    {
        $this->errorResult('Operation not supported');
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Upmind\ProvisionBase\Exception\ProvisionFunctionError
     * @throws \Upmind\ProvisionProviders\AutoLogin\Exceptions\CannotParseResponse
     */
    public function changePackage(ChangePackageParams $params): ChangePackageResult
    {
        $customerId = $params->service_identifier ?: $params->username;

        $planType = $params->package_identifier;

        if (empty($planType)) {
            $this->errorResult('Package identifier (plan type) is required');
        }

        $response = $this->client()->patch("/api/integrations/customer/{$customerId}/info", [
            RequestOptions::JSON => [
                'planType' => $planType,
            ],
        ]);

        $handler = new ResponseHandler($response);
        $handler->assertSuccess();

        return ChangePackageResult::create()
            ->setUsername($customerId)
            ->setPackageIdentifier($planType);
    }

    /**
     * @throws \Upmind\ProvisionBase\Exception\ProvisionFunctionError
     */
    public function renew(AccountIdentifierParams $params): EmptyResult
    {
        $this->errorResult('Operation not supported');
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Upmind\ProvisionProviders\AutoLogin\Exceptions\CannotParseResponse
     */
    public function terminate(AccountIdentifierParams $params): EmptyResult
    {
        return $this->suspend($params);
    }

    protected function getBaseUrl(): string
    {
        return $this->configuration->sandbox
            ? 'https://staging-connect.safeweb.co'
            : 'https://connect.safeweb.co';
    }

    protected function client(): Client
    {
        return new Client([
            'base_uri' => $this->getBaseUrl(),
            RequestOptions::HEADERS => [
                'SW-PARTNER-ID' => $this->configuration->partner_id,
                'SW-API-KEY' => $this->configuration->api_key,
                'Content-Type' => 'application/json',
            ],
            RequestOptions::HTTP_ERRORS => false,
            'handler' => $this->getGuzzleHandlerStack(),
        ]);
    }
}
