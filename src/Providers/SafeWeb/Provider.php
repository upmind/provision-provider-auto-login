<?php

declare(strict_types=1);

namespace Upmind\ProvisionProviders\AutoLogin\Providers\SafeWeb;

use DateTimeZone;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use DateTimeImmutable;
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

/**
 * SafeWeb Login Provider
 *
 * It currently supports monitoring for a single email or domain.
 */
class Provider extends Category implements ProviderInterface
{
    protected Configuration $configuration;
    private ?Client $client = null;

    public static function aboutProvider(): AboutData
    {
        return AboutData::create()
            ->setName('SafeWeb')
            ->setDescription('Manage SafeWeb dark web monitoring accounts.')
            ->setLogoUrl('https://api.upmind.io/images/logos/provision/safeweb-logo.png');
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

        if (empty($params->service_identifier)) {
            $this->errorResult('Service identifier (domain or email) is required');
        }

        $customerReference = (string) $params->user_id;
        $companyName = $params->customer_name ?? $customerReference;
        $email = $params->email;
        $planType = $params->package_identifier ?? 'safeweb-basic';

        // Get Plan ID (UUID), also checks if type/ID is valid.
        $planId = $this->getPlanId($planType);

        // Bill date should be current date in UTC + 1 minute.
        $billedFromDate = new DateTimeImmutable('+5 minute', new DateTimeZone('UTC'));

        $body = [
            'companyName' => $companyName,
            'contactEmail' => $email,
            'customerReference' => $customerReference,
            'alertRecipients' => [$email],
            'price' => isset($params->billing, $params->billing->amount) ? (float) $params->billing->amount : 0.0,
            'currencyCode' => $params->billing->currency ?? 'USD', // fallback to USD if not provided
            'billedFromDate' => $billedFromDate->format('Y-m-d\TH:i:s.v\Z'),
            'planId' => $planId,
            'platformAccess' => true,
        ];

        // We expect either an email or a domain. If invalid domain, allow failing from the API.
        if ($this->isValidEmail($params->service_identifier)) {
            $body['assetsEmails'] = [$params->service_identifier];
        } else {
            $body['assetsDomains'] = [$params->service_identifier];
        }

        try {
            $response = $this->client()->post('/api/integrations/customer/onboard', [
                RequestOptions::JSON => $body,
            ]);
        } catch (RequestException $ex) {
            $this->errorResult(
                'Failed to create account for: ' . $params->service_identifier,
                [],
                [],
                $ex
            );
        }

        $handler = new ResponseHandler($response);
        $handler->assertSuccess();

        $customerId = $handler->getData('customerId');

        return CreateResult::create()
            ->setUsername($customerId)
            ->setServiceIdentifier($params->service_identifier)
            ->setPackageIdentifier($planType);
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Upmind\ProvisionBase\Exception\ProvisionFunctionError
     * @throws \Upmind\ProvisionProviders\AutoLogin\Exceptions\CannotParseResponse
     */
    public function login(AccountIdentifierParams $params): LoginResult
    {
        $customerId = $params->username;

        try {
            $response = $this->client()->post("/api/integrations/customer/{$customerId}/magic-link");
        } catch (RequestException $ex) {
            $this->errorResult(
                'Failed to generate login URL for account: ' . $customerId,
                [],
                [],
                $ex
            );
        }

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
        $customerId = $params->username;

        try {
            $this->offBoardCustomer($customerId);
        } catch (RequestException $ex) {
            $this->errorResult(
                'Failed to suspend account: ' . $customerId,
                [],
                [],
                $ex
            );
        }

        return EmptyResult::create();
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Upmind\ProvisionBase\Exception\ProvisionFunctionError
     * @throws \Upmind\ProvisionProviders\AutoLogin\Exceptions\CannotParseResponse
     */
    public function unsuspend(AccountIdentifierParams $params): EmptyResult
    {
        $customerId = $params->username;

        try {
            $response = $this->client()->post('/api/integrations/customer/reactivate', [
                RequestOptions::JSON => [
                    'customerId' => $customerId,
                ],
            ]);
        } catch (RequestException $ex) {
            $this->errorResult(
                'Failed to unsuspend account: ' . $customerId,
                [],
                [],
                $ex
            );
        }

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
        $customerId = $params->username;

        if (empty($params->package_identifier)) {
            $this->errorResult('Package identifier (plan type) is required');
        }

        // Get Plan ID (UUID), also checks if type/ID is valid.
        $planId = $this->getPlanId($params->package_identifier);

        $payload = [
            'planId' => $planId,
        ];

        // Add price if available.
        if (isset($params->billing, $params->billing->amount)) {
            $payload['price'] = (float) $params->billing->amount;
        }

        try {
            $response = $this->client()->patch("/api/integrations/customer/{$customerId}/info", [
                RequestOptions::JSON => $payload,
            ]);
        } catch (RequestException $ex) {
            $this->errorResult(
                'Failed to  change package (plan) for account: ' . $customerId,
                ['package_identifier' => $params->package_identifier],
                [],
                $ex
            );
        }

        $handler = new ResponseHandler($response);
        $handler->assertSuccess();

        return ChangePackageResult::create()
            ->setUsername($customerId)
            ->setServiceIdentifier($params->service_identifier)
            ->setPackageIdentifier($params->package_identifier);
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
        $customerId = $params->username;

        try {
            $this->offBoardCustomer($customerId);
        } catch (RequestException $ex) {
            $this->errorResult(
                'Failed to terminate account: ' . $customerId,
                [],
                [],
                $ex
            );
        }

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
    private function getPlanId(string $planType): string
    {
        $plans = $this->getPartnerPlans();

        foreach ($plans as $plan) {
            // Skip inactive plans
            if ($plan['status'] !== 'active') {
                continue;
            }

            // First match against the ID, otherwise check against legacy Plan Type
            if ($plan['id'] === $planType || (isset($plan['planType']) && $plan['planType'] === $planType)) {
                return $plan['id'];
            }
        }

        $this->errorResult(sprintf('Package identifier (plan type) `%s` is not valid', $planType));
    }

    /**
     * @return array{
     *     array{
     *         id: string,
     *         displayName: string,
     *         features: array{
     *             feature_name: string,
     *         },
     *         isSystemDefault: bool,
     *         planType?: string, // deprecated, could be removed in the future
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

    private function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
