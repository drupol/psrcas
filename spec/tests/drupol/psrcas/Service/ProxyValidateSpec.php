<?php

declare(strict_types=1);

namespace spec\tests\drupol\psrcas\Service;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PhpSpec\ObjectBehavior;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Log\LoggerInterface;
use spec\drupol\psrcas\Cas;
use Symfony\Component\HttpClient\Psr18Client;
use tests\drupol\psrcas\Service\ProxyValidate;

class ProxyValidateSpec extends ObjectBehavior
{
    public function it_can_check_the_visibility_of_some_methods(CacheItemPoolInterface $cache, CacheItemInterface $cacheItem, LoggerInterface $logger)
    {
        $psr17Factory = new Psr17Factory();

        $serverRequest = new ServerRequest('GET', 'http://from');
        $client = new Psr18Client(Cas::getHttpClientMock());

        $cacheItem
            ->set('pgtId')
            ->willReturn($cacheItem);

        $cacheItem
            ->expiresAfter(300)
            ->willReturn($cacheItem);

        $cacheItem
            ->get()
            ->willReturn('pgtId');

        $cache
            ->save($cacheItem)
            ->willReturn(true);

        $cache
            ->hasItem('pgtIou')
            ->willReturn(true);

        $cache
            ->getItem('pgtIou')
            ->willReturn($cacheItem);

        $this->beConstructedWith($serverRequest, [], Cas::getTestProperties(), $client, $psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory, $cache, $logger);

        $this
            ->getClient()
            ->shouldBeAnInstanceOf(ClientInterface::class);

        $this
            ->getLogger()
            ->shouldBeAnInstanceOf(LoggerInterface::class);

        $this
            ->getCache()
            ->shouldBeAnInstanceOf(CacheItemPoolInterface::class);

        $this
            ->getUriFactory()
            ->shouldBeAnInstanceOf(UriFactoryInterface::class);

        $this
            ->getServerRequest()
            ->shouldBeAnInstanceOf(ServerRequestInterface::class);

        $this
            ->getStreamFactory()
            ->shouldBeAnInstanceOf(StreamFactoryInterface::class);

        $this
            ->getRequestFactory()
            ->shouldBeAnInstanceOf(RequestFactoryInterface::class);

        $this
            ->getResponseFactory()
            ->shouldBeAnInstanceOf(ResponseFactoryInterface::class);

        $this
            ->getRequest()
            ->shouldBeAnInstanceOf(RequestInterface::class);

        $response = [
            'serviceResponse' => [
                'authenticationSuccess' => [
                    'proxyGrantingTicket' => 'pgtIou',
                ],
            ],
        ];

        $this
            ->updateParsedResponseWithPgt($response)
            ->shouldReturn(
                [
                    'serviceResponse' => [
                        'authenticationSuccess' => [
                            'proxyGrantingTicket' => 'pgtId',
                        ],
                    ],
                ]
            );
    }

    public function it_can_detect_when_no_credentials()
    {
        $response = new Response(500);

        $this
            ->getCredentials($response)
            ->shouldBeNull();
    }

    public function it_can_log_debugging_information_when_trying_to_get_unexisting_pgtIou(CacheItemPoolInterface $cache, CacheItemInterface $cacheItem, LoggerInterface $logger)
    {
        $psr17Factory = new Psr17Factory();

        $serverRequest = new ServerRequest('GET', 'http://from');
        $client = new Psr18Client(Cas::getHttpClientMock());

        $cache
            ->hasItem('pgtIou')
            ->willReturn(false);

        $this->beConstructedWith($serverRequest, [], Cas::getTestProperties(), $client, $psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory, $cache, $logger);

        $response = [
            'serviceResponse' => [
                'authenticationSuccess' => [
                    'proxyGrantingTicket' => 'pgtIou',
                ],
            ],
        ];

        $this
            ->updateParsedResponseWithPgt($response)
            ->shouldReturn(null);

        $logger
            ->error('CAS validation failed: pgtIou not found in the cache.', ['pgtIou' => 'pgtIou'])
            ->shouldHaveBeenCalledOnce();
    }

    public function it_can_parse_a_response(CacheItemPoolInterface $cache, LoggerInterface $logger)
    {
        $psr17Factory = new Psr17Factory();

        $serverRequest = new ServerRequest('GET', 'http://from');
        $client = new Psr18Client(Cas::getHttpClientMock());

        $this->beConstructedWith($serverRequest, [], Cas::getTestProperties(), $client, $psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory, $cache, $logger);

        $response = new Response(200, [], 'foo');

        $this
            ->parse($response)
            ->shouldBeArray();

        $logger
            ->error('Unable to parse the response with the specified format {format}.', ['format' => 'XML', 'response' => 'foo'])
            ->shouldHaveBeenCalledOnce();
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ProxyValidate::class);
    }

    public function let(ServerRequestInterface $serverRequest, ClientInterface $client, CacheItemPoolInterface $cache, LoggerInterface $logger)
    {
        $psr17Factory = new Psr17Factory();

        $this->beConstructedWith($serverRequest, [], Cas::getTestProperties(), $client, $psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory, $cache, $logger);
    }
}
