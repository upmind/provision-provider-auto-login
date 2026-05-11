<?php

namespace Upmind\ProvisionProviders\AutoLogin\Providers\Sitepro\Helper;

use GuzzleHttp\Client;
use Upmind\ProvisionProviders\AutoLogin\Data\AccountIdentifierParams;
use Upmind\ProvisionProviders\AutoLogin\Providers\Sitepro\Data\Configuration;

class SiteproApi
{
    protected Client $client;
    protected Configuration $configuration;

    public function __construct(Client $client, Configuration $configuration)
    {
        $this->client = $client;
        $this->configuration = $configuration;
    }

    /**
     * @param string $domain Client domain
     * @param mixed $package_identifier Builder plan identifier
     * @param mixed $client_id Client ID
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createSession(string $domain, $package_identifier, $client_id): mixed
    {
        $publishType = $this->configuration->publish_type;
        $brandId = $this->configuration->brand_id;

        $body = [
            'type' => $publishType,
            'domain' => $domain,
            'username' => '__ask_user__',
            'password' => '__ask_user__',
            'apiUrl' => '__ask_user__',
            'uploadDir' => '__ask_user__',
            'brandId' => $brandId,
            'builderPlan' => $package_identifier,
            'panel' => 'Upmind',
            'baseDomain' => $domain,
            'clientId' => $client_id
        ];

        $response = $this->makeRequest('requestLogin', null, $body, 'POST');

        return $response;
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function makeRequest(
        string $command,
        ?array $params = null,
        ?array $body = null,
        string $method = 'GET'
    ): mixed
    {
        $requestParams = [];

        if ($params) {
            $requestParams['query'] = $params;
        }

        if ($body) {
            $requestParams['body'] = json_encode($body);
        }

        $response = $this->client->request($method, $command, $requestParams);
        
        return $response;
    }

    /**
     * Create a session and return the SSO login URL.
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function login(AccountIdentifierParams $params)
    {
        $domain = $params->service_identifier;
        $package_identifier = $params->package_identifier;
        $client_id = $params->username;

        $response = $this->createSession($domain, $package_identifier, $client_id);

        return $response;
    }
}
