<?php

declare(strict_types=1);

namespace Upmind\ProvisionProviders\AutoLogin\Providers\SafeWeb\ResponseHandlers;

use Upmind\ProvisionProviders\AutoLogin\Exceptions\CannotParseResponse;
use Upmind\ProvisionProviders\AutoLogin\Exceptions\OperationFailed;
use Upmind\ProvisionProviders\AutoLogin\ResponseHandlers\AbstractHandler;

class ResponseHandler extends AbstractHandler
{
    /**
     * @throws CannotParseResponse
     */
    public function assertSuccess(): void
    {
        if ($this->isError()) {
            $errorMessage = sprintf('%s %s', $this->response->getStatusCode(), $this->response->getReasonPhrase());

            try {
                $this->parse(false);

                $responseError = $this->getData('error')
                    ?? $this->getData('message');
                $errorMessage = $responseError ?: $errorMessage;

                throw new CannotParseResponse(sprintf('SafeWeb error: %s', $errorMessage));
            } catch (CannotParseResponse $e) {
                throw (new OperationFailed($e->getMessage(), 0, $e))
                    ->withDebug([
                        'http_code' => $this->response->getStatusCode(),
                        'content_type' => $this->response->getHeaderLine('Content-Type'),
                        'body' => $this->getBody(),
                    ]);
            }
        }
    }
}
