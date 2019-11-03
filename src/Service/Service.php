<?php

declare(strict_types=1);

namespace drupol\psrcas\Service;

use drupol\psrcas\Configuration\PropertiesInterface;
use drupol\psrcas\Handler\Handler;
use drupol\psrcas\Introspection\Contract\ServiceValidate;
use drupol\psrcas\Introspection\Introspector;
use InvalidArgumentException;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;

use function array_key_exists;

use const JSON_ERROR_NONE;

/**
 * Class Service.
 */
abstract class Service extends Handler
{
    /**
     * @var \Psr\Http\Client\ClientInterface
     */
    private $client;

    /**
     * @var \Psr\Http\Message\RequestFactoryInterface
     */
    private $requestFactory;

    /**
     * Service constructor.
     *
     * @param ServerRequestInterface $serverRequest
     * @param array $parameters
     * @param \drupol\psrcas\Configuration\PropertiesInterface $properties
     * @param \Psr\Http\Client\ClientInterface $client
     * @param \Psr\Http\Message\UriFactoryInterface $uriFactory
     * @param ResponseFactoryInterface $responseFactory
     * @param \Psr\Http\Message\RequestFactoryInterface $requestFactory
     * @param \Psr\Http\Message\StreamFactoryInterface $streamFactory
     * @param \Psr\Cache\CacheItemPoolInterface $cache
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        ServerRequestInterface $serverRequest,
        array $parameters,
        PropertiesInterface $properties,
        ClientInterface $client,
        UriFactoryInterface $uriFactory,
        ResponseFactoryInterface $responseFactory,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        CacheItemPoolInterface $cache,
        LoggerInterface $logger
    ) {
        parent::__construct(
            $serverRequest,
            $parameters,
            $properties,
            $uriFactory,
            $responseFactory,
            $streamFactory,
            $cache,
            $logger
        );

        $this->client = $client;
        $this->requestFactory = $requestFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials(ResponseInterface $response): ?ResponseInterface
    {
        try {
            $introspect = Introspector::detect($response);
        } catch (InvalidArgumentException $exception) {
            $this
                ->getLogger()
                ->error($exception->getMessage());

            return null;
        }

        if (false === ($introspect instanceof ServiceValidate)) {
            $this
                ->getLogger()
                ->error(
                    'Service validation failed.',
                    [
                        'response' => (string) $response->getBody(),
                    ]
                );

            return null;
        }

        $parsedResponse = $introspect->getParsedResponse();
        $proxyGrantingTicket = array_key_exists(
            'proxyGrantingTicket',
            $parsedResponse['serviceResponse']['authenticationSuccess']
        );

        if (false === $proxyGrantingTicket) {
            $this
                ->getLogger()
                ->debug('Service validation service successful.');

            return $response->withHeader('Content-Type', 'application/json');
        }

        $parsedResponse = $this->updateParsedResponseWithPgt($parsedResponse);

        if (null === $parsedResponse) {
            return null;
        }

        $body = json_encode($parsedResponse);

        if (false === $body) {
            return null;
        }

        $this
            ->getLogger()
            ->debug('Proxy validation service successful.');

        return $response
            ->withBody($this->getStreamFactory()->createStream($body))
            ->withHeader('Content-Type', 'application/json');
    }

    /**
     * {@inheritdoc}
     */
    public function handle(): ?ResponseInterface
    {
        try {
            $response = $this->getClient()->sendRequest($this->getRequest());
        } catch (ClientExceptionInterface $exception) {
            $this
                ->getLogger()
                ->error($exception->getMessage());

            $response = null;
        }

        return null === $response ? $response : $this->normalize($response);
    }

    /**
     * @return \Psr\Http\Client\ClientInterface
     */
    protected function getClient(): ClientInterface
    {
        return $this->client;
    }

    /**
     * Get the request.
     *
     * @return \Psr\Http\Message\RequestInterface
     */
    protected function getRequest(): RequestInterface
    {
        return $this->getRequestFactory()->createRequest('GET', $this->getUri());
    }

    /**
     * @return \Psr\Http\Message\RequestFactoryInterface
     */
    protected function getRequestFactory(): RequestFactoryInterface
    {
        return $this->requestFactory;
    }

    /**
     * Get the URI.
     *
     * @return \Psr\Http\Message\UriInterface
     */
    abstract protected function getUri(): UriInterface;

    /**
     * Parse the response format.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @return array
     *   The parsed response.
     */
    protected function parse(ResponseInterface $response): array
    {
        $format = $this->getProtocolProperties()['default_parameters']['format'] ?? 'XML';

        try {
            $array = Introspector::parse($response, $format);
        } catch (InvalidArgumentException $exception) {
            $this
                ->getLogger()
                ->error(
                    'Unable to parse the response with the specified format {format}.',
                    [
                        'format' => $format,
                        'response' => (string) $response->getBody(),
                    ]
                );

            $array = [];
        }

        return $array;
    }

    /**
     * @param array $response
     *
     * @return array|null
     */
    protected function updateParsedResponseWithPgt(array $response): ?array
    {
        $pgt = $response['serviceResponse']['authenticationSuccess']['proxyGrantingTicket'];

        $hasPgtIou = $this->getCache()->hasItem($pgt);

        if (false === $hasPgtIou) {
            $this
                ->getLogger()
                ->error('CAS validation failed: pgtIou not found in the cache.', ['pgtIou' => $pgt]);

            return null;
        }

        $response['serviceResponse']['authenticationSuccess']['proxyGrantingTicket'] = $this
            ->getCache()
            ->getItem($pgt)
            ->get();

        return $response;
    }

    /**
     * Normalize a response.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function normalize(ResponseInterface $response): ResponseInterface
    {
        $body = $this->parse($response);

        if ([] === $body) {
            $this
                ->getLogger()
                ->error(
                    'Unable to parse the response during the normalization process.',
                    [
                        'body' => (string) $response->getBody(),
                    ]
                );

            return $response;
        }

        $body = json_encode($body);

        if (false === $body || JSON_ERROR_NONE !== json_last_error()) {
            $this
                ->getLogger()
                ->error(
                    'Unable to encode the response in JSON during the normalization process.',
                    [
                        'body' => (string) $response->getBody(),
                    ]
                );

            return $response;
        }

        $this
            ->getLogger()
            ->debug('Response normalization succeeded.', ['body' => $body]);

        return $response
            ->withBody($this->getStreamFactory()->createStream($body))
            ->withHeader('Content-Type', 'application/json');
    }
}
