<?php

declare(strict_types=1);

namespace Upmind\ProvisionProviders\AutoLogin\Providers\SpamExperts;

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
use Upmind\ProvisionProviders\AutoLogin\Providers\SpamExperts\Data\Configuration;
use Upmind\ProvisionProviders\AutoLogin\Providers\SpamExperts\ResponseHandlers\AddDomainResponseHandler;
use Upmind\ProvisionProviders\AutoLogin\Providers\SpamExperts\ResponseHandlers\AuthTicketResponseHandler;
use Upmind\ProvisionProviders\AutoLogin\Providers\SpamExperts\ResponseHandlers\GetDomainProductsResponseHandler;
use Upmind\ProvisionProviders\AutoLogin\Providers\SpamExperts\ResponseHandlers\ProductListResponseHandler;
use Upmind\ProvisionProviders\AutoLogin\Providers\SpamExperts\ResponseHandlers\RemoveDomainResponseHandler;
use Upmind\ProvisionProviders\AutoLogin\Providers\SpamExperts\ResponseHandlers\SetDomainProductsResponseHandler;

class Provider extends Category implements ProviderInterface
{
    /**
     * @var Configuration
     */
    protected $configuration;

    public static function aboutProvider(): AboutData
    {
        return AboutData::create()
            ->setName('SpamExperts')
            ->setDescription('Create, login to and delete SpamExperts domains')
            ->setLogoUrl('https://api.upmind.io/images/logos/provision/spamexperts-logo_2x.png');
    }

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function create(CreateParams $params): CreateResult
    {
        $domainName = $params->service_identifier;
        $package = $params->package_identifier;

        if (empty($domainName)) {
            throw $this->errorResult('Domain name is required as service identifier');
        }

        if (empty($package)) {
            // default to all available products
            $package = implode(',', $this->getAvailableProducts());
        }

        $this->createDomain($domainName);
        $this->setDomainProducts($domainName, $package);

        return CreateResult::create()
            ->setUsername($domainName)
            ->setServiceIdentifier($domainName)
            ->setPackageIdentifier(implode(',', $this->getDomainProducts($domainName)));
    }

    public function login(AccountIdentifierParams $params): LoginResult
    {
        $domainName = $params->service_identifier ?: $params->username;

        return LoginResult::create()
            ->setUrl($this->getLoginUrl($domainName));
    }

    public function suspend(AccountIdentifierParams $params): EmptyResult
    {
        throw $this->errorResult('Operation not supported');
    }

    public function unsuspend(AccountIdentifierParams $params): EmptyResult
    {
        throw $this->errorResult('Operation not supported');
    }

    /**
     * @throws \Upmind\ProvisionBase\Exception\ProvisionFunctionError
     */
    public function changePackage(ChangePackageParams $params): ChangePackageResult
    {
        $this->errorResult('Operation not supported');
    }

    /**
     * @inheritDoc
     */
    public function renew(AccountIdentifierParams $params): EmptyResult
    {
        $this->errorResult('Operation not supported');
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Upmind\ProvisionProviders\AutoLogin\Exceptions\OperationFailed
     */
    public function terminate(AccountIdentifierParams $params): EmptyResult
    {
        $domainName = $params->service_identifier ?: $params->username;

        $this->removeDomain($domainName);

        return EmptyResult::create();
    }

    protected function createDomain(string $domain): void
    {
        $response = $this->client()->post(sprintf('domain/add/domain/%s', $domain));
        $handler = new AddDomainResponseHandler($response);
        $handler->assertSuccess();
    }

    protected function removeDomain(string $domain): void
    {
        $response = $this->client()->post(sprintf('domain/remove/domain/%s', $domain));
        $handler = new RemoveDomainResponseHandler($response);
        $handler->assertSuccess();
    }

    /**
     * @return string[]
     */
    protected function getAvailableProducts(): array
    {
        $response = $this->client()->post('productslist/get/getavailable/1');
        $handler = new ProductListResponseHandler($response);

        return $handler->getProducts();
    }

    /**
     * @return string[]
     */
    protected function getDomainProducts(string $domain): array
    {
        $response = $this->client()->post(sprintf('domain/getproducts/domain/%s', $domain));
        $handler = new GetDomainProductsResponseHandler($response);

        return $handler->getProducts();
    }

    /**
     * Set the enabled products on a domain.
     *
     * @param string $domain
     * @param string|string[] $products Array or CSV of products to sync this domain to
     */
    protected function setDomainProducts(string $domain, $package): void
    {
        if (is_array($package)) {
            $package = implode(',', $package);
        }
        $package = strtolower($package);

        $allProducts = [
            'incoming',
            'outgoing',
            'archiving'
        ];
        $setProducts = [];
        foreach ($allProducts as $product) {
            $setProducts[] = $product . '/' . intval(Str::contains($package, $product));
        }

        $response = $this->client()->post(
            sprintf('domain/setproducts/domain/%s/%s', $domain, implode('/', $setProducts))
        );
        $handler = new SetDomainProductsResponseHandler($response);
        $handler->assertSuccess();
    }

    /**
     * @param string $username Username or domain name
     *
     * @return string Login url
     */
    protected function getLoginUrl(string $username): string
    {
        $response = $this->client()->post(sprintf('authticket/create/username/%s', $username));
        $handler = new AuthTicketResponseHandler($response);

        return $this->configuration->dashboard_url . sprintf('?authticket=%s', $handler->getTicket());
    }

    protected function client(): Client
    {
        return new Client([
            'base_uri' => rtrim($this->configuration->dashboard_url, '/') . '/api/',
            RequestOptions::AUTH => [ // basic auth
                $this->configuration->username,
                $this->configuration->password
            ],
            RequestOptions::HTTP_ERRORS => false,
            'handler' => $this->getGuzzleHandlerStack(!!$this->configuration->debug)
        ]);
    }
}
