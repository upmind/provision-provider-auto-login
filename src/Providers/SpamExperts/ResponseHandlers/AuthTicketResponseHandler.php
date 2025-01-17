<?php

declare(strict_types=1);

namespace Upmind\ProvisionProviders\AutoLogin\Providers\SpamExperts\ResponseHandlers;

use Illuminate\Support\Str;
use Upmind\ProvisionProviders\AutoLogin\Exceptions\CannotParseResponse;
use Upmind\ProvisionProviders\AutoLogin\Providers\SpamExperts\Exceptions\ResponseMissingAuthTicket;

/**
 * Handler to parse an auth ticket from a PSR-7 response body.
 */
class AuthTicketResponseHandler extends ResponseHandler
{
    /**
     * Extract an auth ticket from the response.
     *
     * @return string Valid auth ticket
     *
     * @throws ResponseMissingAuthTicket If auth ticket cannot be determined
     */
    public function getTicket(): string
    {
        try {
            $this->assertSuccess();

            $ticket = $this->getBody();
        } catch (CannotParseResponse $e) {
            throw (new ResponseMissingAuthTicket($e->getMessage(), 0, $e))
                ->withDebug([
                    'http_code' => $this->response->getStatusCode(),
                    'content_type' => $this->response->getHeaderLine('Content-Type'),
                    'body' => $this->getBody(),
                    'ticket' => null, // Ticket should not be set at this point.
                ]);
        }

        // If ticket is not valid, throw relevant exception with debug info.
        if (!$this->isValidTicket($ticket)) {
            throw (new ResponseMissingAuthTicket('Unable to parse valid auth ticket from service response', 0))
                ->withDebug([
                    'http_code' => $this->response->getStatusCode(),
                    'content_type' => $this->response->getHeaderLine('Content-Type'),
                    'body' => $this->getBody(),
                    'ticket' => $ticket,
                ]);
        }

        return $ticket;
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
     * @throws \Upmind\ProvisionProviders\AutoLogin\Exceptions\OperationFailed If "Add Domain" failed
     */
    public function assertSuccess(): void
    {
        parent::assertSuccess();

        $body = strtolower($this->getBody());

        // If no error in the body string, return without failure.
        if (!Str::startsWith($body, 'error:')) {
            return;
        }

        // Otherwise, handle the error cases & message.
        if (Str::containsAll($body, ['domain', 'not registered'])) {
            throw new CannotParseResponse('Domain name doesn\'t exist');
        }

        if (Str::contains($body, 'no valid user')) {
            throw new CannotParseResponse('Service account doesn\'t exist');
        }

        throw new CannotParseResponse('Failed to get domain name auth ticket');
    }
}
