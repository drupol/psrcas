<?php

declare(strict_types=1);

namespace drupol\psrcas\Cas;

use drupol\psrcas\Utils\Uri;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;

/**
 * Class AbstractCasProtocol.
 */
abstract class AbstractCasProtocol implements CasProtocolInterface
{
    /**
     * The cache.
     *
     * @var \Psr\Cache\CacheItemPoolInterface
     */
    private $cache;

    /**
     * The HTTP client.
     *
     * @var \Psr\Http\Client\ClientInterface
     */
    private $client;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * The protocol properties.
     *
     * @var mixed[]
     */
    private $properties;

    /**
     * @var \Psr\Http\Message\ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * The stream factory.
     *
     * @var \Psr\Http\Message\StreamFactoryInterface
     */
    private $streamFactory;

    /**
     * The URI factory.
     *
     * @var \Psr\Http\Message\UriFactoryInterface
     */
    private $uriFactory;

    /**
     * AbstractCasProtocol constructor.
     *
     * @param array $properties
     * @param \Psr\Http\Client\ClientInterface $client
     * @param \Psr\Http\Message\UriFactoryInterface $uriFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Psr\Http\Message\ResponseFactoryInterface $responseFactory
     * @param \Psr\Cache\CacheItemPoolInterface $cache
     * @param StreamFactoryInterface $streamFactory
     */
    public function __construct(
        array $properties,
        ClientInterface $client,
        UriFactoryInterface $uriFactory,
        LoggerInterface $logger,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
        CacheItemPoolInterface $cache
    ) {
        $this->properties = $properties;
        $this->client = $client;
        $this->uriFactory = $uriFactory;
        $this->logger = $logger;
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory;
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function getCache(): CacheItemPoolInterface
    {
        return $this->cache;
    }

    /**
     * {@inheritdoc}
     */
    public function getHttpClient(): ClientInterface
    {
        return $this->client;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * {@inheritdoc}
     */
    public function getResponseFactory(): ResponseFactoryInterface
    {
        return $this->responseFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getStreamFactory(): StreamFactoryInterface
    {
        return $this->streamFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getUriFactory(): UriFactoryInterface
    {
        return $this->uriFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function isServiceValidateResponseValid(ResponseInterface $response): bool
    {
        $xml = $this->parseResponse($response);

        if (null === $xml) {
            return false;
        }

        // If no <cas:authenticationSuccess> tag.
        if ($xml->xpath('cas:authenticationSuccess') === []) {
            $this
                ->logger
                ->error(
                    'Invalid CAS response.',
                    [
                        'response' => (string) $response->getBody(),
                    ]
                );

            return false;
        }

        if ([] !== $pgtIou = $xml->xpath('cas:authenticationSuccess//cas:proxyGrantingTicket')) {
            try {
                $item = $this->getCache()->hasItem((string) $pgtIou[0]);
            } catch (\Exception $e) {
                $item = false;
            }

            if (false === $item) {
                $this
                    ->getLogger()
                    ->error(
                        'Unable to validate the response because the pgtIou was not found.'
                    );

                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function parseProxyTicketResponse(ResponseInterface $response): ?string
    {
        $xml = $this->parseResponse($response);

        if (null === $xml) {
            return null;
        }

        // If no <cas:authenticationSuccess> tag.
        if ($xml->xpath('cas:proxySuccess') === []) {
            $this
                ->logger
                ->error(
                    'Invalid CAS response.',
                    [
                        'response' => (string) $response->getBody(),
                    ]
                );

            return null;
        }

        if ([] !== $pgt = $xml->xpath('cas:proxySuccess//cas:proxyTicket')) {
            return (string) $pgt[0];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function parseResponse(ResponseInterface $response): ?SimpleXMLElement
    {
        $parsed = null;
        $contentType = \current($response->getHeader('Content-Type'));

        if (false === $contentType) {
            return null;
        }

        if (0 === \mb_strpos($contentType, 'text/xml')) {
            try {
                $parsed = new SimpleXMLElement(
                    (string) $response->getBody(),
                    0,
                    false,
                    'cas',
                    true
                );
            } catch (\Exception $e) {
                $this
                    ->logger
                    ->error(
                        $e->getMessage(),
                        ['response' => $response->getBody()]
                    );
            }
        }

        return $parsed;
    }

    /**
     * @param array $parameters
     * @param ServerRequestInterface $request
     *
     * @return array
     */
    protected function formatProtocolParameters(ServerRequestInterface $request, array $parameters): array
    {
        $uri = $request->getUri();

        // If no service is provided, get the request referer.
        // If no request referer, then use the URI from the request.
        $parameters['service'] = $parameters['service'] ??
            $request->getHeaders()['referer'][0] ?? (string) $request->getUri();

        $parameters['service'] = Uri::removeParams(
            $this->getUriFactory()->createUri(
                $parameters['service']
            ),
            'ticket'
        );

        $parameters = \array_filter(
            $parameters,
            static function ($parameter) {
                return false !== $parameter;
            }
        );

        if (\array_key_exists('gateway', $parameters)) {
            if (true === $parameters['gateway']) {
                $parameters['gateway'] = 'true';
                $parameters['service'] = Uri::withParam($parameters['service'], 'gateway', '0');

                if (Uri::hasParams($uri, 'gateway')) {
                    if ('true' !== Uri::getParam($uri, 'gateway')) {
                        return [];
                    }
                }
            }
        }

        if (\array_key_exists('renew', $parameters)) {
            if (true === $parameters['renew']) {
                $parameters['renew'] = 'true';
                $parameters['service'] = Uri::withParam($parameters['service'], 'renew', '0');

                if (Uri::hasParams($uri, 'renew')) {
                    if ('true' !== Uri::getParam($uri, 'renew')) {
                        return [];
                    }
                }
            }
        }

        $parameters['service'] = (string) $parameters['service'];

        return $parameters;
    }

    /**
     * @param \Psr\Http\Message\UriInterface $from
     * @param string $name
     * @param array $query
     *
     * @return \Psr\Http\Message\UriInterface
     */
    protected function get(UriInterface $from, string $name, array $query = []): UriInterface
    {
        $properties = $this->getProperties();

        $properties += [
            'protocol' => [
                $name => [],
            ],
        ];

        $properties['protocol'][$name] += [
            'query' => [],
            'allowed_parameters' => [],
        ];

        $query += $properties['protocol'][$name]['query'];

        // Remove parameters that are not allowed.
        $query = \array_intersect_key(
            $query,
            (array) \array_combine(
                $properties['protocol'][$name]['allowed_parameters'],
                $properties['protocol'][$name]['allowed_parameters']
            )
        );

        $baseUrl = \parse_url($properties['base_url']);

        if (false === $baseUrl) {
            $baseUrl = ['path' => ''];
            $properties['base_url'] = '';
        }

        $baseUrl += ['path' => ''];

        return ($this->getUriFactory())
            ->createUri($properties['base_url'])
            ->withPath($baseUrl['path'] . $properties['protocol'][$name]['path'])
            ->withQuery(\http_build_query(Uri::getParams($from) + $query))
            ->withFragment($from->getFragment());
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return null|\Psr\Http\Message\ResponseInterface
     */
    protected function validateCasRequest(ServerRequestInterface $request): ?ResponseInterface
    {
        try {
            $response = $this->getHttpClient()->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            $this->logger->error($e->getMessage());
            $response = null;
        }

        if (null === $response) {
            return null;
        }

        if (200 !== $response->getStatusCode()) {
            $this
                ->logger
                ->error(
                    \sprintf(
                        'Invalid status code (%s) for request URI (%s).',
                        $response->getStatusCode(),
                        $request->getUri()
                    )
                );

            return null;
        }

        $contentType = $response->getHeader('Content-Type');

        if ([] === $contentType) {
            $this
                ->logger
                ->error('Unable to find the "Content-Type" header in the response.');

            return null;
        }

        return $response;
    }
}
