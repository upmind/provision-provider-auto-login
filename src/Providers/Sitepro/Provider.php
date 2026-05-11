<?php

declare(strict_types=1);

namespace Upmind\ProvisionProviders\AutoLogin\Providers\Sitepro;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use Upmind\ProvisionBase\Provider\Contract\ProviderInterface;
use Upmind\ProvisionBase\Provider\DataSet\AboutData;
use Upmind\ProvisionProviders\AutoLogin\Category;
use Upmind\ProvisionProviders\AutoLogin\Data\AccountIdentifierParams;
use Upmind\ProvisionProviders\AutoLogin\Data\CreateParams;
use Upmind\ProvisionProviders\AutoLogin\Data\CreateResult;
use Upmind\ProvisionProviders\AutoLogin\Data\EmptyResult;
use Upmind\ProvisionProviders\AutoLogin\Data\LoginResult;
use Upmind\ProvisionProviders\AutoLogin\Providers\Sitepro\Data\Configuration;
use Upmind\ProvisionProviders\AutoLogin\Providers\Sitepro\Helper\SiteproApi;
use Upmind\ProvisionProviders\AutoLogin\Providers\Sitepro\ResponseHandlers\UrlResponseHandler;

class Provider extends Category implements ProviderInterface
{
    /**
     * @var Configuration
     */
    protected $configuration;

    protected SiteproApi|null $api = null;

    public static function aboutProvider(): AboutData
    {
        return AboutData::create()
            ->setName('Site.pro')
            ->setLogoUrl('https://api.upmind.io/images/logos/provision/sitepro-logo@2x.png')
            ->setDescription(
                'Create, manage and log into Site.pro site builder accounts'
            );
    }

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Upmind\ProvisionProviders\AutoLogin\Providers\Sitepro\Exceptions\ResponseMissingUrl
     */
    public function login(AccountIdentifierParams $params): LoginResult
    {
        if (empty($params->service_identifier)) {
            $this->errorResult('A service domain was not found for this product');
        }

        if (empty($params->package_identifier)) {
            $this->errorResult('A builder plan must be configured for this product');
        }

        try {
            $response = $this->api()->login($params);
        } catch (\Throwable $e) {
            $this->handleException($e);
        }

        $handler = new UrlResponseHandler($response);

        return LoginResult::create()
            ->setUrl($handler->getUrl());
    }

    public function create(CreateParams $params): CreateResult
    {
        $this->errorResult('Not Implemented');
    }

    public function suspend(AccountIdentifierParams $params): EmptyResult
    {
        $this->errorResult('Not Implemented');
    }

    public function unsuspend(AccountIdentifierParams $params): EmptyResult
    {
        $this->errorResult('Not Implemented');
    }

    public function changePackage(AccountIdentifierParams $params): EmptyResult
    {
        $this->errorResult('Not Implemented');
    }

    public function renew(AccountIdentifierParams $params): EmptyResult
    {
        $this->errorResult('Not Implemented');
    }

    public function terminate(AccountIdentifierParams $params): EmptyResult
    {
        $this->errorResult('Not Implemented');
    }

    protected function getExtraConfigurationParams(): array
    {
        $extraParams = [];

        for ($i = 1; $i <= 3; $i++) {
            $extraData = $this->configuration->{"extra_data_{$i}"};
            $extraSecret = $this->configuration->{"extra_secret_{$i}"};

            if ($extraData) {
                $extraParams["data_{$i}"] = $extraData;
            }

            if ($extraSecret) {
                $extraParams["secret_{$i}"] = $extraSecret;
            }
        }

        return $extraParams;
    }

    /**
     * @return no-return
     *
     * @throws \Upmind\ProvisionBase\Exception\ProvisionFunctionError
     * @throws \Throwable
     */
    protected function handleException(\Throwable $e, $params = null): void
    {
        if ($e instanceof TransferException) {
            $errorMessage = 'Provider Connection Failed';
            $errorData = [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ];

            if (($e instanceof RequestException) && $e->hasResponse()) {
                $response = $e->getResponse();
                $errorMessage = 'Provider API Error';

                $body = trim($response === null ? '' : $response->getBody()->__toString());
                $responseData = json_decode($body, true);

                $error = $responseData['error'] ?? null;
                $errorMessage =  $error ?? $response->getReasonPhrase();
                $errorData = [
                    'response_data' => $responseData
                ];
            }

            $this->errorResult($errorMessage, $errorData, [], $e);
        }

        throw $e;
    }

    public function api(): SiteproApi
    {
        if (isset($this->api)) {
            return $this->api;
        }

        $baseUri = $this->configuration->api_url;

        $credentials = base64_encode("{$this->configuration->username}:{$this->configuration->password}");

        $client = new Client([
            'base_uri' => $baseUri,
            RequestOptions::HEADERS => [
                'User-Agent' => 'upmind/provision-provider-auto-login v1.0',
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => 'Basic ' . $credentials,
            ],
            RequestOptions::COOKIES => new \GuzzleHttp\Cookie\CookieJar(),
            RequestOptions::TIMEOUT => 30, // seconds
            RequestOptions::CONNECT_TIMEOUT => 5, // seconds
            'handler' => $this->getGuzzleHandlerStack()
        ]);

        return $this->api = new SiteproApi($client, $this->configuration);
    }
}
