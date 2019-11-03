<?php

declare(strict_types=1);

namespace spec\drupol\psrcas\Service;

use drupol\psrcas\Service\Proxy;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PhpSpec\ObjectBehavior;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use spec\drupol\psrcas\Cas;

class ProxySpec extends ObjectBehavior
{
    public function it_can_detect_a_wrong_proxy_response(ServerRequestInterface $serverRequest, ClientInterface $client, CacheItemPoolInterface $cache, LoggerInterface $logger)
    {
        $body = <<< 'EOF'
<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas">
 <cas:authenticationSuccess>
  <cas:user>username</cas:user>
  <cas:proxyGrantingTicket>pgtIou</cas:proxyGrantingTicket>
  <cas:proxies>
    <cas:proxy>http://app/proxyCallback.php</cas:proxy>
  </cas:proxies>
 </cas:authenticationSuccess>
</cas:serviceResponse>
EOF;

        $response = new Response(200, ['Content-Type' => 'application/xml'], $body);

        $this
            ->getCredentials($response)
            ->shouldBeNull();
    }

    public function it_can_detect_when_no_credentials()
    {
        $response = new Response(500);

        $this
            ->getCredentials($response)
            ->shouldBeNull();
    }

    public function it_is_initializable(ServerRequestInterface $serverRequest, ClientInterface $client, CacheItemPoolInterface $cache, LoggerInterface $logger)
    {
        $this->shouldHaveType(Proxy::class);
    }

    public function let(ServerRequestInterface $serverRequest, ClientInterface $client, CacheItemPoolInterface $cache, LoggerInterface $logger)
    {
        $psr17Factory = new Psr17Factory();

        $this->beConstructedWith($serverRequest, [], Cas::getTestProperties(), $client, $psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory, $cache, $logger);
    }
}
