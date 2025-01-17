<?php

declare(strict_types=1);

namespace Upmind\ProvisionProviders\AutoLogin\Providers\SpamExperts\ResponseHandlers;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Upmind\ProvisionProviders\AutoLogin\Exceptions\CannotParseResponse;
use Upmind\ProvisionProviders\AutoLogin\Providers\SpamExperts\Exceptions\ResponseMissingAuthTicket;
use Upmind\ProvisionProviders\AutoLogin\ResponseHandlers\AbstractHandler;

/**
 * Handler to parse an auth ticket from a PSR-7 response body.
 */
class AuthTicketResponseHandler extends ResponseHandler
{
    /**
     * Extract an auth ticket from the response.
     *
     * @throws ResponseMissingAuthTicket If auth ticket cannot be determined
     */
    public function getTicket(): string
    {
        try {
            $this->assertSuccess();

            $ticket = $this->getBody();

            if (!$this->isValidTicket($ticket)) {
                throw new CannotParseResponse('Unable to parse valid auth ticket from service response');
            }

            return $ticket;
        } catch (CannotParseResponse $e) {
            throw (new ResponseMissingAuthTicket($e->getMessage(), 0, $e))
                ->withDebug([
                    'http_code' => $this->response->getStatusCode(),
                    'content_type' => $this->response->getHeaderLine('Content-Type'),
                    'body' => $this->getBody(),
                    'ticket' => $ticket ?? null,
                ]);
        }
    }

    /**
     * Determine whether the given auth ticket is valid.
     *
     * @param string|null $ticket
     *
     * @return bool
     */
    protected function isValidTicket($ticket): bool
    {
        if (!is_string($ticket)) {
            return false;
        }

        return strlen($ticket) === 40 && ctype_xdigit($ticket);
    }

    /**
     * Assert 'Add Domain' was successful.
     *
     * @throws OperationFailed If "Add Domain" failed
     *
     * @return void
     */
    public function assertSuccess(): void
    {
        parent::assertSuccess();

        $body = strtolower($this->getBody());

        if (Str::startsWith($body, 'error:')) {
            if (Str::containsAll($body, ['domain', 'not registered'])) {
                throw new CannotParseResponse('Domain name doesn\'t exist');
            }

            if (Str::contains($body, 'no valid user')) {
                throw new CannotParseResponse('Service account doesn\'t exist');
            }

            throw new CannotParseResponse('Failed to get domain name auth ticket');
        }
    }
}
