<?php

declare(strict_types=1);

namespace spec\tests\drupol\psrcas;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use PhpSpec\ObjectBehavior;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use spec\drupol\psrcas\Cas as CasSpecUtils;
use Symfony\Component\HttpClient\Psr18Client;

class CasSpec extends ObjectBehavior
{
    public function it_can_test_the_proxy_mode_with_pgtUrl(CacheItemPoolInterface $cache, LoggerInterface $logger)
    {
        $properties = CasSpecUtils::getTestPropertiesWithPgtUrl();
        $client = new Psr18Client(CasSpecUtils::getHttpClientMock());

        $psr17Factory = new Psr17Factory();
        $creator = new ServerRequestCreator(
            $psr17Factory, // ServerRequestFactory
            $psr17Factory, // UriFactory
            $psr17Factory, // UploadedFileFactory
            $psr17Factory  // StreamFactory
        );
        $serverRequest = $creator->fromGlobals();

        $this->beConstructedWith($serverRequest, $properties, $client, $psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory, $cache, $logger);

        $this
            ->proxyMode()
            ->shouldBe(true);
    }

    public function it_can_test_the_proxy_mode_without_pgtUrl(CacheItemPoolInterface $cache, LoggerInterface $logger)
    {
        $properties = CasSpecUtils::getTestProperties();
        $client = new Psr18Client(CasSpecUtils::getHttpClientMock());

        $psr17Factory = new Psr17Factory();
        $creator = new ServerRequestCreator(
            $psr17Factory, // ServerRequestFactory
            $psr17Factory, // UriFactory
            $psr17Factory, // UploadedFileFactory
            $psr17Factory  // StreamFactory
        );
        $serverRequest = $creator->fromGlobals();

        $this->beConstructedWith($serverRequest, $properties, $client, $psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory, $cache, $logger);

        $this
            ->proxyMode()
            ->shouldBe(false);
    }
}
