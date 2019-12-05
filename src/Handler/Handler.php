<?php

declare(strict_types=1);

namespace drupol\psrcas\Handler;

use drupol\psrcas\Configuration\PropertiesInterface;
use drupol\psrcas\Utils\Uri;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;

use function array_key_exists;

/**
 * Class Handler.
 */
abstract class Handler
{
    /**
     * @var \Psr\Cache\CacheItemPoolInterface
     */
    private $cache;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var array[]|string[]
     */
    private $parameters;

    /**
     * @var \drupol\psrcas\Configuration\PropertiesInterface
     */
    private $properties;

    /**
     * @var \Psr\Http\Message\ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * @var \Psr\Http\Message\ServerRequestInterface
     */
    private $serverRequest;

    /**
     * @var \Psr\Http\Message\StreamFactoryInterface
     */
    private $streamFactory;

    /**
     * @var \Psr\Http\Message\UriFactoryInterface
     */
    private $uriFactory;

    /**
     * Handler constructor.
     *
     * @param ServerRequestInterface $serverRequest
     * @param array[]|string[] $parameters
     * @param \drupol\psrcas\Configuration\PropertiesInterface $properties
     * @param \Psr\Http\Message\UriFactoryInterface $uriFactory
     * @param ResponseFactoryInterface $responseFactory
     * @param \Psr\Http\Message\StreamFactoryInterface $streamFactory
     * @param \Psr\Cache\CacheItemPoolInterface $cache
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        ServerRequestInterface $serverRequest,
        array $parameters,
        PropertiesInterface $properties,
        UriFactoryInterface $uriFactory,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        CacheItemPoolInterface $cache,
        LoggerInterface $logger
    ) {
        $this->serverRequest = $serverRequest;
        $this->parameters = $parameters;
        $this->properties = $properties;
        $this->uriFactory = $uriFactory;
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    /**
     * @param \Psr\Http\Message\UriInterface $from
     * @param string $name
     * @param string[]|UriInterface[] $query
     *
     * @return \Psr\Http\Message\UriInterface
     */
    protected function buildUri(UriInterface $from, string $name, array $query = []): UriInterface
    {
        $properties = $this->getProperties();

        // Remove parameters that are not allowed.
        $query = array_intersect_key(
            $query,
            (array) array_combine(
                $properties['protocol'][$name]['allowed_parameters'] ?? [],
                $properties['protocol'][$name]['allowed_parameters'] ?? []
            )
        ) + Uri::getParams($from);

        $baseUrl = parse_url($properties['base_url']);

        if (false === $baseUrl) {
            $baseUrl = ['path' => ''];
            $properties['base_url'] = '';
        }

        $baseUrl += ['path' => ''];

        if (true === array_key_exists('service', $query)) {
            $query['service'] = (string) $query['service'];
        }

        // Filter out empty $query parameters
        $query = array_filter(
            $query,
            static function (string $item): bool {
                return '' !== $item;
            }
        );

        return $this->getUriFactory()
            ->createUri($properties['base_url'])
            ->withPath($baseUrl['path'] . $properties['protocol'][$name]['path'])
            ->withQuery(http_build_query($query))
            ->withFragment($from->getFragment());
    }

    /**
     * @param array[]|bool[]|string[] $parameters
     *
     * @return string[]
     */
    protected function formatProtocolParameters(array $parameters): array
    {
        $parameters = array_filter(
            $parameters
        );

        $parameters = array_map(
            static function ($parameter) {
                return true === $parameter ? 'true' : $parameter;
            },
            $parameters
        );

        if (true === array_key_exists('service', $parameters)) {
            $service = $this->getUriFactory()->createUri(
                $parameters['service']
            );

            $service = Uri::removeParams(
                $service,
                'ticket'
            );

            $parameters['service'] = (string) $service;
        }

        return $parameters;
    }

    /**
     * @return \Psr\Cache\CacheItemPoolInterface
     */
    protected function getCache(): CacheItemPoolInterface
    {
        return $this->cache;
    }

    /**
     * @return \Psr\Log\LoggerInterface
     */
    protected function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @return array[]
     */
    protected function getParameters(): array
    {
        return $this->parameters + ($this->getProtocolProperties()['default_parameters'] ?? []);
    }

    /**
     * @return PropertiesInterface
     */
    protected function getProperties(): PropertiesInterface
    {
        return $this->properties;
    }

    /**
     * Get the scoped properties of the protocol endpoint.
     *
     * @return array[]
     */
    protected function getProtocolProperties(): array
    {
        return [];
    }

    /**
     * @return \Psr\Http\Message\ResponseFactoryInterface
     */
    protected function getResponseFactory(): ResponseFactoryInterface
    {
        return $this->responseFactory;
    }

    /**
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    protected function getServerRequest(): ServerRequestInterface
    {
        return $this->serverRequest;
    }

    /**
     * @return \Psr\Http\Message\StreamFactoryInterface
     */
    protected function getStreamFactory(): StreamFactoryInterface
    {
        return $this->streamFactory;
    }

    /**
     * @return \Psr\Http\Message\UriFactoryInterface
     */
    protected function getUriFactory(): UriFactoryInterface
    {
        return $this->uriFactory;
    }
}
