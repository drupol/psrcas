<?php

declare(strict_types=1);

namespace spec\drupol\psrcas\Introspection;

use drupol\psrcas\Introspection\Introspector;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PhpSpec\ObjectBehavior;

class ProxySpec extends ObjectBehavior
{
    public function it_can_detect_a_proxy_response()
    {
        $psr17Factory = new Psr17Factory();

        $body = <<< 'EOF'
<?xml version="1.0" encoding="utf-8"?>
<cas:serviceResponse xmlns:cas="https://ecas.ec.europa.eu/cas/schemas"
                     server="ECAS MOCKUP version 4.6.0.20924 - 09/02/2016 - 14:37"
                     date="2019-10-18T12:17:53.069+02:00" version="4.5">
	<cas:proxySuccess>
		<cas:proxyTicket>PT-214-A3OoEPNr4Q9kNNuYzmfN8azU31aDUsuW8nk380k7wDExT5PFJpxR1TrNI3q3VGzyDdi0DpZ1LKb8IhPKZKQvavW-8hnfexYjmLCx7qWNsLib1W-DCzzoLVTosAUFzP3XDn5dNzoNtxIXV9KSztF9fYhwHvU0</cas:proxyTicket>
	</cas:proxySuccess>
</cas:serviceResponse>
EOF;

        $response = (new Response(200))
            ->withHeader('Content-Type', 'application/xml')
            ->withBody($psr17Factory->createStream($body));

        $this
            ->beConstructedWith(Introspector::parse($response), 'XML', $response);

        $this
            ->getProxyTicket()
            ->shouldReturn('PT-214-A3OoEPNr4Q9kNNuYzmfN8azU31aDUsuW8nk380k7wDExT5PFJpxR1TrNI3q3VGzyDdi0DpZ1LKb8IhPKZKQvavW-8hnfexYjmLCx7qWNsLib1W-DCzzoLVTosAUFzP3XDn5dNzoNtxIXV9KSztF9fYhwHvU0');

        $this
            ->getFormat()
            ->shouldReturn('XML');

        $this
            ->getResponse()
            ->shouldReturn($response);
    }
}
