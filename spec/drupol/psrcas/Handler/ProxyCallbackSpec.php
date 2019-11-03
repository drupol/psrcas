<?php

declare(strict_types=1);

namespace spec\drupol\psrcas\Handler;

use drupol\psrcas\Handler\ProxyCallback;
use Exception;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PhpSpec\ObjectBehavior;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use spec\drupol\psrcas\Cas;

class ProxyCallbackSpec extends ObjectBehavior
{
    public function it_can_catch_issue_with_the_cache(ServerRequestInterface $serverRequest, CacheItemPoolInterface $cache, CacheItemInterface $cacheItem, LoggerInterface $logger)
    {
        $psr17Factory = new Psr17Factory();

        $serverRequest = new ServerRequest('GET', 'http://from?pgtId=pgtId&pgtIou=pgtIou');

        $cacheItem
            ->set('pgtId')
            ->willReturn($cacheItem);

        $cacheItem
            ->expiresAfter(300)
            ->willReturn($cacheItem);

        $uniqid = uniqid('ErrorMessageHere', true);

        $cache
            ->getItem('pgtIou')
            ->willThrow(new Exception($uniqid));

        $cache
            ->save($cacheItem)
            ->willReturn(true);

        $this->beConstructedWith($serverRequest, [], Cas::getTestProperties(), $psr17Factory, $psr17Factory, $psr17Factory, $cache, $logger);

        $this
            ->handle();

        $logger
            ->error($uniqid)
            ->shouldHaveBeenCalledOnce();
    }

    public function it_can_test_if_the_cache_is_working(ServerRequestInterface $serverRequest, CacheItemPoolInterface $cache, CacheItemInterface $cacheItem, LoggerInterface $logger)
    {
        $this
            ->handle();

        $cache
            ->save($cacheItem)
            ->shouldHaveBeenCalledOnce();
    }

    public function it_can_test_the_logger_when_missing_pgtId(ServerRequestInterface $serverRequest, CacheItemPoolInterface $cache, LoggerInterface $logger)
    {
        $psr17Factory = new Psr17Factory();

        $serverRequest = new ServerRequest('GET', 'http://from?pgtIou=pgtIou');

        $this->beConstructedWith($serverRequest, [], Cas::getTestProperties(), $psr17Factory, $psr17Factory, $psr17Factory, $cache, $logger);

        $this
            ->handle();

        $logger
            ->debug('Missing proxy callback parameter (pgtId).')
            ->shouldHaveBeenCalledOnce();
    }

    public function it_can_test_the_logger_when_missing_pgtIou(ServerRequestInterface $serverRequest, CacheItemPoolInterface $cache, LoggerInterface $logger)
    {
        $psr17Factory = new Psr17Factory();

        $serverRequest = new ServerRequest('GET', 'http://from?pgtId=pgtId');

        $this->beConstructedWith($serverRequest, [], Cas::getTestProperties(), $psr17Factory, $psr17Factory, $psr17Factory, $cache, $logger);

        $this
            ->handle();

        $logger
            ->debug('Missing proxy callback parameter (pgtIou).')
            ->shouldHaveBeenCalledOnce();
    }

    public function it_can_test_the_logger_when_no_parameter_is_in_the_url(ServerRequestInterface $serverRequest, CacheItemPoolInterface $cache, LoggerInterface $logger)
    {
        $psr17Factory = new Psr17Factory();

        $serverRequest = new ServerRequest('GET', 'http://from');

        $this->beConstructedWith($serverRequest, [], Cas::getTestProperties(), $psr17Factory, $psr17Factory, $psr17Factory, $cache, $logger);

        $this
            ->handle();

        $logger
            ->debug('CAS server just checked the proxy callback endpoint.')
            ->shouldHaveBeenCalledOnce();
    }

    public function it_can_test_the_logger_when_parameters_are_in_the_url(ServerRequestInterface $serverRequest, CacheItemPoolInterface $cache, CacheItemInterface $cacheItem, LoggerInterface $logger)
    {
        $psr17Factory = new Psr17Factory();

        $serverRequest = new ServerRequest('GET', 'http://from?pgtId=pgtId&pgtIou=pgtIou');

        $cacheItem
            ->set('pgtId')
            ->willReturn($cacheItem);

        $cacheItem
            ->expiresAfter(300)
            ->willReturn($cacheItem);

        $cache
            ->getItem('pgtIou')
            ->willReturn($cacheItem);

        $cache
            ->save($cacheItem)
            ->willReturn(true);

        $this->beConstructedWith($serverRequest, [], Cas::getTestProperties(), $psr17Factory, $psr17Factory, $psr17Factory, $cache, $logger);

        $this
            ->handle();

        $logger
            ->debug('Storing proxy callback parameters (pgtId and pgtIou).')
            ->shouldHaveBeenCalledOnce();
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ProxyCallback::class);
    }

    public function let(ServerRequestInterface $serverRequest, CacheItemPoolInterface $cache, CacheItemInterface $cacheItem, LoggerInterface $logger)
    {
        $psr17Factory = new Psr17Factory();

        $serverRequest = new ServerRequest('GET', 'http://from?pgtId=pgtId&pgtIou=pgtIou');

        $cacheItem
            ->set('pgtId')
            ->willReturn($cacheItem);

        $cacheItem
            ->expiresAfter(300)
            ->willReturn($cacheItem);

        $cache
            ->getItem('pgtIou')
            ->willReturn($cacheItem);

        $cache
            ->save($cacheItem)
            ->willReturn(true);

        $this->beConstructedWith($serverRequest, [], Cas::getTestProperties(), $psr17Factory, $psr17Factory, $psr17Factory, $cache, $logger);
    }
}
