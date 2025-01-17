<?php

declare(strict_types=1);

namespace Upmind\ProvisionProviders\AutoLogin\Providers\SpamExperts\ResponseHandlers;

use Illuminate\Support\Arr;
use Upmind\ProvisionProviders\AutoLogin\Exceptions\CannotParseResponse;
use Upmind\ProvisionProviders\AutoLogin\Exceptions\OperationFailed;
use Upmind\ProvisionProviders\AutoLogin\Providers\SpamExperts\Exceptions\ResponseMissingAuthTicket;
use Upmind\ProvisionProviders\AutoLogin\ResponseHandlers\AbstractHandler;

/**
 * Handler to parse product list from a PSR-7 response body.
 */
class ProductListResponseHandler extends ResponseHandler
{
    /**
     * Extract products list from the response.
     *
     * @throws OperationFailed If auth ticket cannot be determined
     *
     * @return string[] List of available products
     */
    public function getProducts(): array
    {
        try {
            $this->assertSuccess();

            $this->parseJson();

            $productList = $this->getData();

            if (!$this->isValidProductList($productList)) {
                throw new CannotParseResponse('Unable to parse valid product list from service response');
            }

            return $productList;
        } catch (CannotParseResponse $e) {
            throw (new OperationFailed($e->getMessage(), 0, $e))
                ->withDebug([
                    'http_code' => $this->response->getStatusCode(),
                    'content_type' => $this->response->getHeaderLine('Content-Type'),
                    'body' => $this->getBody(),
                    'ticket' => $ticket ?? null,
                ]);
        }
    }

    /**
     * Determine whether the given product list is valid.
     *
     * @param array|null $productList
     *
     * @return bool
     */
    protected function isValidProductList($productList): bool
    {
        if (!is_array($productList)) {
            return false;
        }

        foreach ($productList as $product) {
            if (!is_string($product) || empty($product)) {
                return false;
            }
        }

        return true;
    }
}
