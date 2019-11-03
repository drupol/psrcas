<?php

declare(strict_types=1);

namespace spec\drupol\psrcas\Utils;

use drupol\psrcas\Utils\SimpleXml;
use PhpSpec\ObjectBehavior;
use SimpleXMLElement;

class SimpleXmlSpec extends ObjectBehavior
{
    public function it_can_convert_xml_from_a_string_into_an_array()
    {
        $data = <<< 'EOF'
<?xml version="1.0" encoding="utf-8"?>
<cas:serviceResponse xmlns:cas="https://ecas.ec.europa.eu/cas/schemas"
                     server="ECAS MOCKUP version 4.6.0.20924 - 09/02/2016 - 14:37"
                     date="2019-10-18T12:17:53.069+02:00" version="4.5">
	<cas:proxySuccess>
		<cas:proxyTicket>PT-214-A3OoEPNr4Q9kNNuYzmfN8azU31aDUsuW8nk380k7wDExT5PFJpxR1TrNI3q3VGzyDdi0DpZ1LKb8IhPKZKQvavW-8hnfexYjmLCx7qWNsLib1W-DCzzoLVTosAUFzP3XDn5dNzoNtxIXV9KSztF9fYhwHvU0</cas:proxyTicket>
	</cas:proxySuccess>
</cas:serviceResponse>
EOF;

        $this::fromString($data)
            ->shouldBeAnInstanceOf(SimpleXMLElement::class);
    }

    public function it_can_convert_xml_into_an_array()
    {
        $data = <<< 'EOF'
<?xml version="1.0" encoding="utf-8"?>
<cas:serviceResponse xmlns:cas="https://ecas.ec.europa.eu/cas/schemas"
                     server="ECAS MOCKUP version 4.6.0.20924 - 09/02/2016 - 14:37"
                     date="2019-10-18T12:17:53.069+02:00" version="4.5">
	<cas:proxySuccess>
		<cas:proxyTicket>PT-214-A3OoEPNr4Q9kNNuYzmfN8azU31aDUsuW8nk380k7wDExT5PFJpxR1TrNI3q3VGzyDdi0DpZ1LKb8IhPKZKQvavW-8hnfexYjmLCx7qWNsLib1W-DCzzoLVTosAUFzP3XDn5dNzoNtxIXV9KSztF9fYhwHvU0</cas:proxyTicket>
	</cas:proxySuccess>
</cas:serviceResponse>
EOF;
        $xml = $this->getWrappedObject()::fromString($data);

        $this::toArray($xml)
            ->shouldReturn(
                [
                    'serviceResponse' => [
                        'proxySuccess' => [
                            'proxyTicket' => 'PT-214-A3OoEPNr4Q9kNNuYzmfN8azU31aDUsuW8nk380k7wDExT5PFJpxR1TrNI3q3VGzyDdi0DpZ1LKb8IhPKZKQvavW-8hnfexYjmLCx7qWNsLib1W-DCzzoLVTosAUFzP3XDn5dNzoNtxIXV9KSztF9fYhwHvU0',
                        ],
                    ],
                ]
            );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(SimpleXml::class);
    }
}
