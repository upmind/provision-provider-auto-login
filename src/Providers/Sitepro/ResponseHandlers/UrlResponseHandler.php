<?php

declare(strict_types=1);

namespace Upmind\ProvisionProviders\AutoLogin\Providers\Sitepro\ResponseHandlers;

use Upmind\ProvisionProviders\AutoLogin\Exceptions\CannotParseResponse;
use Upmind\ProvisionProviders\AutoLogin\Providers\Sitepro\Exceptions\ResponseMissingUrl;
use Upmind\ProvisionProviders\AutoLogin\ResponseHandlers\AbstractHandler;

/**
 * Handler to parse a URL from a PSR-7 response body.
 */
class UrlResponseHandler extends AbstractHandler
{
    /**
     * Extract a URL from the response.
     *
     * @param string|null $property Name of the property containing the URL
     * @return string Valid URL
     *
     * @throws ResponseMissingUrl If URL cannot be determined
     */
    public function getUrl(?string $property = 'url'): string
    {
        try {
            $url = $this->getData($property);

            if (!$this->isValidUrl($url) && isset($property)) {
                $url = $this->getData(); //try entire parsed response data
            }

            if (!$this->isValidUrl($url)) {
                $url = $this->getBody(); // try raw response body
            }

            if (!$this->isValidUrl($url)) {
                throw new CannotParseResponse(
                    sprintf('Unable to parse valid %s from service response', $property ?: 'URL')
                );
            }

            return $url;
        } catch (CannotParseResponse $e) {
            throw (new ResponseMissingUrl($e->getMessage(), 0, $e))
                ->withDebug([
                    'http_code' => $this->response->getStatusCode(),
                    'content_type' => $this->response->getHeaderLine('Content-Type'),
                    'body' => $this->getBody(),
                    'url' => $url ?? null,
                ]);
        }
    }

    /**
     * Determine whether the given url is valid and contains a scheme (protocol),
     * host and one or more of: path, query & fragment.
     *
     * @param string|null $url
     */
    protected function isValidUrl($url): bool
    {
        if (!is_string($url)) {
            return false;
        }

        $components = parse_url((string)$url);

        return !empty($components['scheme'])
            && !empty($components['host'])
            && (
                !empty($components['path'])
                || !empty($components['query'])
                || !empty($components['fragment'])
            );
    }
}
