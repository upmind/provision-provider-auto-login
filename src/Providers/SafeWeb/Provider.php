<?php

declare(strict_types=1);

namespace Upmind\ProvisionProviders\AutoLogin\Providers\SafeWeb;

use DateTime;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Str;
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
    private ?Client $client = null;

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

        // Get Plan UUID, also checks if type/uuid is valid.
        $planUuid = $this->getPlanUuid($planType);

        $billedFromDate = (new DateTime('+1 month', new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.v\Z');

        $body = [
            'companyName' => $companyName,
            'contactEmail' => $email,
            'customerReference' => $customerReference,
            'alertRecipients' => [$email],
            'price' => $params->extra['price'] ?? 0,
            'billedFromDate' => $billedFromDate,
            'currencyCode' => $this->configuration->currency_code,
            'planUuid' => $planUuid,
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
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Upmind\ProvisionBase\Exception\ProvisionFunctionError
     * @throws \Upmind\ProvisionProviders\AutoLogin\Exceptions\CannotParseResponse
     */
    public function suspend(AccountIdentifierParams $params): EmptyResult
    {
        $customerId = $params->service_identifier ?: $params->username;

        $this->offBoardCustomer($customerId);

        return EmptyResult::create();
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Upmind\ProvisionBase\Exception\ProvisionFunctionError
     * @throws \Upmind\ProvisionProviders\AutoLogin\Exceptions\CannotParseResponse
     */
    public function unsuspend(AccountIdentifierParams $params): EmptyResult
    {
        $customerId = $params->service_identifier ?: $params->username;

        $response = $this->client()->post('/api/integrations/customer/reactivate', [
            RequestOptions::JSON => [
                'customerId' => $customerId,
            ],
        ]);

        $handler = new ResponseHandler($response);
        $handler->assertSuccess();

        return EmptyResult::create();
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

        // Get Plan UUID, also checks if type/uuid is valid.
        $planUuid = $this->getPlanUuid($planType);

        $response = $this->client()->patch("/api/integrations/customer/{$customerId}/info", [
            RequestOptions::JSON => [
                'planUuid' => $planUuid,
            ],
        ]);

        $handler = new ResponseHandler($response);
        $handler->assertSuccess();

        return ChangePackageResult::create()
            ->setUsername($customerId)
            ->setPackageIdentifier($planType);
    }

    public function renew(AccountIdentifierParams $params): EmptyResult
    {
        return EmptyResult::create();
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Upmind\ProvisionBase\Exception\ProvisionFunctionError
     * @throws \Upmind\ProvisionProviders\AutoLogin\Exceptions\CannotParseResponse
     */
    public function terminate(AccountIdentifierParams $params): EmptyResult
    {
        $customerId = $params->service_identifier ?: $params->username;

        $this->offBoardCustomer($customerId);

        return EmptyResult::create();
    }

    protected function getBaseUrl(): string
    {
        return $this->configuration->isSandbox()
            ? 'https://staging-connect.safeweb.co'
            : 'https://connect.safeweb.co';
    }

    protected function client(): Client
    {
        if ($this->client !== null) {
            return $this->client;
        }

       $this->client = new Client([
            'base_uri' => $this->getBaseUrl(),
            RequestOptions::HEADERS => [
                'SW-PARTNER-ID' => $this->configuration->partner_id,
                'SW-API-KEY' => $this->configuration->api_key,
                'Content-Type' => 'application/json',
            ],
            RequestOptions::HTTP_ERRORS => false,
            'handler' => $this->getGuzzleHandlerStack(),
        ]);

        return $this->client;
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Upmind\ProvisionBase\Exception\ProvisionFunctionError
     * @throws \Upmind\ProvisionProviders\AutoLogin\Exceptions\CannotParseResponse
     */
    private function getPlanUuid(string $planType): string
    {
        $plans = $this->getPartnerPlans();

        foreach ($plans as $plan) {
            // Skip inactive plans
            if ($plan['status'] !== 'active') {
                continue;
            }

            if ($plan['planType'] === $planType || $plan['uuid'] === $planType) {
                return $plan['uuid'];
            }
        }

        $this->errorResult('Package identifier (plan type) is not valid');
    }

    /**
     * @return array{
     *     array{
     *         uuid: string,
     *         displayName: string,
     *         features: array{
     *             feature_name: string,
     *         },
     *         isSystemDefault: bool,
     *         planType: string,
     *         status: string,
     *         allowsInsurance: bool
     *     }
     * }
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Upmind\ProvisionBase\Exception\ProvisionFunctionError
     * @throws \Upmind\ProvisionProviders\AutoLogin\Exceptions\CannotParseResponse
     */
    private function getPartnerPlans(): array
    {
        $response = $this->client()->get('/api/integrations/partner/plans');

        $handler = new ResponseHandler($response);

        $handler->assertSuccess();

        $data = $handler->getData();

        if (empty($data['data'])) {
            $this->errorResult('No available plans found');
        }

        return $data['data'];
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Upmind\ProvisionBase\Exception\ProvisionFunctionError
     * @throws \Upmind\ProvisionProviders\AutoLogin\Exceptions\CannotParseResponse
     */
    private function offBoardCustomer(string $customerId): void
    {
        $response = $this->client()->post('/api/integrations/customer/offboard', [
            RequestOptions::JSON => [
                'customerId' => $customerId,
            ],
        ]);

        $handler = new ResponseHandler($response);
        $handler->assertSuccess();
    }
}
