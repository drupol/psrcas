<?php

declare(strict_types=1);

namespace drupol\psrcas\Cas;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;

/**
 * Interface CasProtocolInterface.
 */
interface CasProtocolInterface
{
    /**
     * Get the cache.
     *
     * @return \Psr\Cache\CacheItemPoolInterface
     */
    public function getCache(): CacheItemPoolInterface;

    /**
     * Get the http client.
     *
     * @return \Psr\Http\Client\ClientInterface
     *   The http client.
     */
    public function getHttpClient(): ClientInterface;

    /**
     * Get the logger.
     *
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger(): LoggerInterface;

    /**
     * Get the library properties.
     *
     * @return array
     *   The properties.
     */
    public function getProperties(): array;

    /**
     * Get the response factory.
     *
     * @return \Psr\Http\Message\ResponseFactoryInterface
     */
    public function getResponseFactory(): ResponseFactoryInterface;

    /**
     * Get the stream factory.
     *
     * @return \Psr\Http\Message\StreamFactoryInterface
     */
    public function getStreamFactory(): StreamFactoryInterface;

    /**
     * Get the URI factory.
     *
     * @return \Psr\Http\Message\UriFactoryInterface
     */
    public function getUriFactory(): UriFactoryInterface;

    /**
     * Handle the request made on the proxy callback URL.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *   The request.
     *
     * @return \Psr\Http\Message\ResponseInterface
     *   The response.
     */
    public function handleProxyCallback(ServerRequestInterface $request): ResponseInterface;

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
     * @return null|\Psr\Http\Message\ResponseInterface
     *   An HTTP response or null.
     */
    public function login(ServerRequestInterface $request, array $parameters = []): ?ResponseInterface;

    /**
     * Redirect to CAS logout.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param array $parameters
     *
     * @return \Psr\Http\Message\ResponseInterface
     *   An HTTP response.
     */
    public function logout(ServerRequestInterface $request, array $parameters = []): ResponseInterface;

    /**
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @return null|string
     */
    public function parseProxyTicketResponse(ResponseInterface $response): ?string;

    /**
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @return null|\SimpleXMLElement
     */
    public function parseResponse(ResponseInterface $response): ?SimpleXMLElement;

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param array $parameters
     *
     * @return null|\Psr\Http\Message\ResponseInterface
     */
    public function requestProxyTicket(ServerRequestInterface $request, array $parameters = []): ?ResponseInterface;

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param array $parameters
     *
     * @return null|\Psr\Http\Message\ResponseInterface
     */
    public function requestServiceValidate(ServerRequestInterface $request, array $parameters): ?ResponseInterface;
}
