<?php

declare(strict_types=1);

namespace drupol\psrcas\Cas;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriFactoryInterface;

/**
 * Interface CasProtocolInterface.
 */
interface CasProtocolInterface
{
    /**
     * Get the http client.
     *
     * @return \Psr\Http\Client\ClientInterface
     *   The http client.
     */
    public function getHttpClient(): ClientInterface;

    /**
     * Get the library properties.
     *
     * @return array
     *   The properties.
     */
    public function getProperties(): array;

    /**
     * Get the URI factory.
     *
     * @return \Psr\Http\Message\UriFactoryInterface
     */
    public function getUriFactory(): UriFactoryInterface;

    /**
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @return bool
     */
    public function isServiceValidateResponseValid(ResponseInterface $response): bool;

    /**
     * If not authenticated, redirect to CAS login.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param array $parameters
     *
     * @return \Psr\Http\Message\ResponseInterface
     *   Null if user is already authenticated, the HTTP Response otherwise.
     */
    public function login(
        ServerRequestInterface $request,
        array $parameters = []
    ): ?ResponseInterface;

    /**
     * Redirect to CAS logout.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param array $parameters
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function logout(
        ServerRequestInterface $request,
        array $parameters = []
    ): ResponseInterface;

    /**
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @return null|\SimpleXMLElement
     */
    public function parseResponse(ResponseInterface $response): ?\SimpleXMLElement;

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param array $parameters
     *
     * @return null|\Psr\Http\Message\ResponseInterface
     */
    public function serviceValidate(
        ServerRequestInterface $request,
        array $parameters
    ): ?ResponseInterface;
}
