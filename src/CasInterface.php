<?php

declare(strict_types=1);

namespace drupol\psrcas;

use drupol\psrcas\Configuration\PropertiesInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Interface CasInterface.
 */
interface CasInterface
{
    /**
     * Authenticate the request.
     *
     * @return array[]|null
     *   The user response if authenticated, null otherwise.
     */
    public function authenticate(): ?array;

    /**
     * Get the CAS properties.
     *
     * @return \drupol\psrcas\Configuration\PropertiesInterface
     *   The properties.
     */
    public function getProperties(): PropertiesInterface;

    /**
     * Handle the request made on the proxy callback URL.
     *
     * @param array[]|string[] $parameters
     *   The parameters related to the service.
     * @param \Psr\Http\Message\ResponseInterface|null $response
     *   If provided, use that Response.
     *
     * @return \Psr\Http\Message\ResponseInterface|null
     *   An HTTP response or null.
     */
    public function handleProxyCallback(
        array $parameters = [],
        ?ResponseInterface $response = null
    ): ?ResponseInterface;

    /**
     * If not authenticated, redirect to CAS login.
     *
     * @param array[]|string[] $parameters
     *   The parameters related to the service.
     *
     * @return \Psr\Http\Message\ResponseInterface|null
     *   An HTTP response or null.
     */
    public function login(array $parameters = []): ?ResponseInterface;

    /**
     * Redirect to CAS logout.
     *
     * @param array[]|string[] $parameters
     *   The parameters related to the service.
     *
     * @return \Psr\Http\Message\ResponseInterface|null
     *   An HTTP response or null.
     */
    public function logout(array $parameters = []): ?ResponseInterface;

    /**
     * Request a proxy ticket.
     *
     * @param array[]|string[] $parameters
     *   The parameters related to the service.
     * @param \Psr\Http\Message\ResponseInterface|null $response
     *   If provided, use that Response.
     *
     * @return \Psr\Http\Message\ResponseInterface|null
     *   An HTTP response or null.
     */
    public function requestProxyTicket(
        array $parameters = [],
        ?ResponseInterface $response = null
    ): ?ResponseInterface;

    /**
     * Request a proxy validation.
     *
     * @param array[]|string[] $parameters
     *   The parameters related to the service.
     * @param \Psr\Http\Message\ResponseInterface|null $response
     *   If provided, use that Response.
     *
     * @return \Psr\Http\Message\ResponseInterface|null
     *   An HTTP response or null.
     */
    public function requestProxyValidate(
        array $parameters = [],
        ?ResponseInterface $response = null
    ): ?ResponseInterface;

    /**
     * Request a service validation.
     *
     * @param array[]|string[] $parameters
     *   The parameters related to the service.
     * @param \Psr\Http\Message\ResponseInterface|null $response
     *   If provided, use that Response.
     *
     * @return \Psr\Http\Message\ResponseInterface|null
     *   An HTTP response or null.
     */
    public function requestServiceValidate(
        array $parameters = [],
        ?ResponseInterface $response = null
    ): ?ResponseInterface;

    /**
     * Request a ticket validation.
     *
     * @param array[]|string[] $parameters
     *   The parameters related to the service.
     * @param \Psr\Http\Message\ResponseInterface|null $response
     *   If provided, use that Response.
     *
     * @return \Psr\Http\Message\ResponseInterface|null
     *   An HTTP response or null.
     */
    public function requestTicketValidation(
        array $parameters = [],
        ?ResponseInterface $response = null
    ): ?ResponseInterface;

    /**
     * Check if the request needs to be authenticated.
     *
     * @return bool
     *   True if it can run the authentication, false otherwise.
     */
    public function supportAuthentication(): bool;

    /**
     * Update the server request in use.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $serverRequest
     *   The server request.
     *
     * @return \drupol\psrcas\CasInterface
     *   The cas service.
     */
    public function withServerRequest(ServerRequestInterface $serverRequest): CasInterface;
}
