<?php

declare(strict_types=1);

namespace spec\drupol\psrcas\Cas\Protocol;

use drupol\psrcas\Cas\Protocol\Cas;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PhpSpec\ObjectBehavior;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Component\HttpClient\Response\MockResponse;

class CasSpec extends ObjectBehavior
{
    public function it_can_be_constructed_without_base_url(LoggerInterface $logger, CacheItemPoolInterface $cache)
    {
        $properties = [
            'base_url' => '//////',
            'protocol' => [
                'login' => [
                    'path' => '/login',
                    'allowed_parameters' => [
                        'coin',
                    ],
                ],
            ],
        ];

        $client = new Psr18Client($this->getHttpClientMock());
        $uriFactory = $responseFactory = new Psr17Factory();

        $this->beConstructedWith($properties, $client, $uriFactory, $logger, $responseFactory, $cache);

        $request = new ServerRequest('GET', 'http://foo');

        $this
            ->login($request)
            ->getHeaders()
            ->shouldReturn(['Location' => ['/login']]);
    }

    public function it_can_deal_with_bad_requests(LoggerInterface $logger, CacheItemPoolInterface $cache)
    {
        $properties = [
            'base_url' => 'http://local/cas',
            'protocol' => [
                'servicevalidate' => [
                    'path' => '/serviceValidate',
                    'allowed_parameters' => [
                        'ticket',
                        'service',
                    ],
                ],
            ],
        ];

        $client = new Psr18Client($this->getHttpClientMock());
        $uriFactory = $responseFactory = new Psr17Factory();

        $this->beConstructedWith($properties, $client, $uriFactory, $logger, $responseFactory, $cache);

        $request = new ServerRequest('GET', 'http://local/cas/serviceValidate?service=service&ticket=ticket&invalid_ticket=true', ['Content-Type' => 'text/xml']);

        $this
            ->serviceValidate($request, 'service', 'ticket')
            ->shouldBeAnInstanceOf(ResponseInterface::class);

        $this
            ->isServiceValidateResponseValid(
                $this->serviceValidate($request, 'service', 'ticket')->getWrappedObject()
            )
            ->shouldReturn(false);
    }

    public function it_can_detect_when_gateway_and_renew_are_set_together()
    {
        $from = 'http://local/';

        $request = new ServerRequest('GET', $from);

        $this
            ->login($request, null, true, true)
            ->shouldBeNull();
    }

    public function it_can_detect_wrong_url(LoggerInterface $logger, CacheItemPoolInterface $cache)
    {
        $properties = [
            'base_url' => '',
            'protocol' => [
                'servicevalidate' => [
                    'path' => '\?&!@# // \\ http:// foo bar',
                ],
            ],
        ];

        $client = new Psr18Client($this->getHttpClientMock());
        $uriFactory = $responseFactory = new Psr17Factory();

        $this->beConstructedWith($properties, $client, $uriFactory, $logger, $responseFactory, $cache);

        $logger
            ->error('Invalid URL: no "base_uri" option was provided and host or scheme is missing in "%5C%3F&!@%23%20//%20%5C%20http://%20foo%20bar".')
            ->shouldBeCalled();

        $request = new ServerRequest('GET', 'error');

        $this
            ->serviceValidate($request, '', '')
            ->shouldBeNull();
    }

    public function it_can_gateway_login()
    {
        $from = 'http://local/';

        $request = new ServerRequest('GET', $from);

        $this
            ->login($request, null, false, true)
            ->getHeader('Location')
            ->shouldReturn(['http://local/cas/login?service=http%3A%2F%2Flocal%2F%3Fgateway%3D0']);

        $request = new ServerRequest('GET', $from . '?gateway=false');

        $this
            ->login($request, null, false, true)
            ->shouldBeNull();
    }

    public function it_can_get_a_logger()
    {
        $this
            ->getLogger()
            ->shouldBeAnInstanceOf(LoggerInterface::class);
    }

    public function it_can_get_a_response_factory()
    {
        $this
            ->getResponseFactory()
            ->shouldReturnAnInstanceOf(ResponseFactoryInterface::class);
    }

    public function it_can_get_the_cache()
    {
        $this
            ->getCache()
            ->shouldBeAnInstanceOf(CacheItemPoolInterface::class);
    }

    public function it_can_login()
    {
        $request = new ServerRequest('GET', 'http://local/', ['referer' => 'http://google.com/']);

        $this
            ->login($request)
            ->shouldReturnAnInstanceOf(ResponseInterface::class);

        $this
            ->login($request)
            ->getStatusCode()
            ->shouldReturn(302);

        $request = new ServerRequest('GET', 'http://local/');

        $this
            ->login($request)
            ->getHeader('Location')
            ->shouldReturn(['http://local/cas/login?service=http%3A%2F%2Flocal%2F']);

        $request = new ServerRequest('GET', 'http://local/');

        $this
            ->login($request, 'http://foo.bar/', false, false, null, null, null, null, null, null, ['foo' => 'bar'])
            ->getHeader('Location')
            ->shouldReturn(['http://local/cas/login?service=http%3A%2F%2Ffoo.bar%2F']);

        $this
            ->login($request, null, false, false, null, null, null, null, null, null, ['custom' => 'foo'])
            ->getHeader('Location')
            ->shouldReturn(['http://local/cas/login?custom=foo&service=http%3A%2F%2Flocal%2F']);

        $request = new ServerRequest('GET', 'http://local/', ['referer' => 'http://referer/']);

        $this
            ->login($request, 'http://foo.bar/', false, false, null, null, null, null, null, null, ['foo' => 'bar'])
            ->getHeader('Location')
            ->shouldReturn(['http://local/cas/login?service=http%3A%2F%2Ffoo.bar%2F']);

        $this
            ->login($request, null, false, false, null, null, null, null, null, null, ['custom' => 'foo'])
            ->getHeader('Location')
            ->shouldReturn(['http://local/cas/login?custom=foo&service=http%3A%2F%2Freferer%2F']);
    }

    public function it_can_logout()
    {
        $request = new ServerRequest('GET', 'http://local/');

        $this
            ->logout($request)
            ->shouldReturnAnInstanceOf(ResponseInterface::class);

        $this
            ->logout($request)
            ->getStatusCode()
            ->shouldReturn(302);

        $this
            ->logout($request)
            ->getHeader('Location')
            ->shouldReturn(['http://local/cas/logout?service=http%3A%2F%2Flocal%2F']);

        $this
            ->logout($request, null, ['custom' => 'bar'])
            ->getHeader('Location')
            ->shouldReturn(['http://local/cas/logout?custom=bar&service=http%3A%2F%2Flocal%2F']);

        $this
            ->logout($request, 'http://custom.local/', ['custom' => 'bar'])
            ->getHeader('Location')
            ->shouldReturn(['http://local/cas/logout?custom=bar&service=http%3A%2F%2Fcustom.local%2F']);

        $request = new ServerRequest('GET', 'http://local/', ['referer' => 'http://referer/']);

        $this
            ->logout($request, null, ['custom' => 'bar'])
            ->getHeader('Location')
            ->shouldReturn(['http://local/cas/logout?custom=bar&service=http%3A%2F%2Freferer%2F']);

        $this
            ->logout($request, 'http://custom.local/', ['custom' => 'bar'])
            ->getHeader('Location')
            ->shouldReturn(['http://local/cas/logout?custom=bar&service=http%3A%2F%2Fcustom.local%2F']);

        $this
            ->logout($request, 'service')
            ->shouldReturnAnInstanceOf(ResponseInterface::class);
    }

    public function it_can_renew_login()
    {
        $from = 'http://local/';

        $request = new ServerRequest('GET', $from);

        $this
            ->login($request, null, true)
            ->getHeader('Location')
            ->shouldReturn(['http://local/cas/login?service=http%3A%2F%2Flocal%2F%3Frenew%3D0']);

        $request = new ServerRequest('GET', $from . '?renew=false');

        $this
            ->login($request, null, true)
            ->shouldBeNull();
    }

    public function it_can_validate_a_service_ticket(LoggerInterface $logger, CacheItemPoolInterface $cache)
    {
        $properties = [
            'base_url' => '',
            'protocol' => [
                'servicevalidate' => [
                    'path' => 'http://local/cas/serviceValidate',
                    'allowed_parameters' => [
                        'service',
                        'ticket',
                        'http_code',
                        'invalid_xml',
                    ],
                ],
            ],
        ];

        $client = new Psr18Client($this->getHttpClientMock());
        $uriFactory = $responseFactory = new Psr17Factory();

        $this->beConstructedWith($properties, $client, $uriFactory, $logger, $responseFactory, $cache);

        $request = new ServerRequest('GET', 'http://local/cas/serviceValidate?service=service&ticket=ticket', ['Content-Type' => 'text/xml']);

        $this
            ->serviceValidate($request, 'service', 'ticket')
            ->shouldReturnAnInstanceOf(ResponseInterface::class);

        $this
            ->serviceValidate($request, 'service', 'ticket')
            ->getStatusCode()
            ->shouldReturn(200);

        $logger
            ->error('')
            ->shouldNotBeCalled();

        $this
            ->serviceValidate($request, 'service', 'ticket')
            ->shouldReturnAnInstanceOf(ResponseInterface::class);

        $request = new ServerRequest('GET', 'http://local/cas/serviceValidate?service=service&ticket=ticket&http_code=404');

        $logger
            ->error('Invalid status code (404) for request URI (http://local/cas/serviceValidate?service=service&ticket=ticket&http_code=404).')
            ->shouldBeCalled();

        $this
            ->serviceValidate($request, 'service', 'ticket', null, false, null, ['http_code' => '404'])
            ->shouldBeNull();

        $logger
            ->error('Unable to find the "Content-Type" header in the response.')
            ->shouldBeCalled();

        $request = new ServerRequest('GET', 'http://local/cas/serviceValidate?service=service&ticket=ticket&invalid_xml=true');

        $this
            ->serviceValidate($request, 'service', 'ticket', null, false, null, ['invalid_xml' => 'true'])
            ->shouldNotBeNull();

        $logger
            ->error('Invalid status code (404) for request URI (http://local/cas/serviceValidate?service=service&ticket=ticket&http_code=404).')
            ->shouldBeCalled();

        $request = new ServerRequest('GET', 'http://local/cas/serviceValidate?service=service&ticket=ticket&http_code=404');

        $this
            ->serviceValidate($request, 'service', 'ticket', null, false, null, ['http_code' => 404])
            ->shouldBeNull();

        $request = new ServerRequest('GET', 'http://local/cas/serviceValidate?service=service&ticket=ticket');

        $this
            ->serviceValidate($request, 'service', 'ticket', null, true)
            ->shouldReturnAnInstanceOf(ResponseInterface::class);

        $request = new ServerRequest('GET', 'http://local/cas/serviceValidate?service=service&ticket=ticket&renew=0');

        $this
            ->serviceValidate($request, 'service', 'ticket', null, true)
            ->shouldBeNull();

        $logger
            ->error('Unable to find the "Content-Type" header in the response.')
            ->shouldBeCalled();

        $request = new ServerRequest('POST', 'foo');

        $this
            ->serviceValidate($request, '', '')
            ->shouldBeNull();

        $from = 'http://local/cas/serviceValidate?service=service&ticket=ticket&invalid_header=true';

        $request = new ServerRequest('GET', $from);

        $this
            ->serviceValidate($request, 'service', 'ticket', null, false, null, ['invalid_header' => 'true'])
            ->shouldBeAnInstanceOf(ResponseInterface::class);

        $from = 'http://local/cas/serviceValidate?service=service&ticket=ticket&renew=true';

        $request = new ServerRequest('GET', $from);

        $this
            ->serviceValidate($request, 'service', 'ticket', null, true)
            ->shouldBeAnInstanceOf(ResponseInterface::class);

        $from = 'http://local/cas/serviceValidate?service=service&ticket=ticket&renew=0';

        $request = new ServerRequest('GET', $from);

        $this
            ->serviceValidate($request, 'service', 'ticket', null, true)
            ->shouldBeNull();

        $from = 'http://local/cas/serviceValidate?service=service&ticket=ticket&renew=false';

        $this
            ->serviceValidate(new ServerRequest('GET', $from), 'service', 'ticket', null, true)
            ->shouldBeNull();
    }

    public function it_can_verify_if_a_serviceValidate_request_is_valid(LoggerInterface $logger, CacheItemPoolInterface $cache)
    {
        $from = 'http://local/';

        $request = new ServerRequest('GET', $from);

        $this
            ->serviceValidate($request, 'service', 'ticket')
            ->shouldNotBeNull();

        $this
            ->isServiceValidateResponseValid(
                $this
                    ->serviceValidate($request, 'service', 'ticket')
                    ->getWrappedObject()
            )
            ->shouldReturn(true);

        $from = 'http://local/cas/serviceValidate?service=service&ticket=ticket&invalid_xml=true';

        $request = new ServerRequest('GET', $from);

        $response = $this
            ->serviceValidate($request, 'service', 'ticket', null, false, null, ['invalid_xml' => 'true']);

        $response
            ->shouldBeAnInstanceOf(ResponseInterface::class);

        $logger
            ->error('String could not be parsed as XML', ['response' => (string) $response->getWrappedObject()->getBody()])
            ->shouldBeCalled();

        $this
            ->isServiceValidateResponseValid(
                $this
                    ->serviceValidate($request, 'service', 'ticket', null, false, null, ['invalid_xml' => 'true'])
                    ->getWrappedObject()
            )
            ->shouldReturn(false);

        $from = 'http://local/cas/serviceValidate?service=service&ticket=ticket&invalid_ticket=true';

        $request = new ServerRequest('GET', $from);

        $response = $this
            ->serviceValidate($request, 'service', 'ticket', null, false, null, ['invalid_ticket' => 'true']);

        $response
            ->shouldBeAnInstanceOf(ResponseInterface::class);

        $logger
            ->error(
                'Invalid CAS response.',
                [
                    'response' => (string) $response->getWrappedObject()->getBody(),
                ]
            )
            ->shouldBeCalled();

        $this
            ->isServiceValidateResponseValid(
                $this
                    ->serviceValidate($request, 'service', 'ticket', null, false, null, ['invalid_ticket' => 'true'])
                    ->getWrappedObject()
            )
            ->shouldReturn(false);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Cas::class);
    }

    public function let(LoggerInterface $logger, CacheItemPoolInterface $cache)
    {
        $properties = [
            'base_url' => 'http://local/cas',
            'protocol' => [
                'login' => [
                    'path' => '/login',
                    'allowed_parameters' => [
                        'service',
                        'custom',
                    ],
                ],
                'logout' => [
                    'path' => '/logout',
                    'allowed_parameters' => [
                        'service',
                        'custom',
                    ],
                ],
                'servicevalidate' => [
                    'path' => '/serviceValidate',
                    'allowed_parameters' => [
                        'ticket',
                        'service',
                        'custom',
                    ],
                ],
            ],
        ];

        $client = new Psr18Client($this->getHttpClientMock());
        $uriFactory = $responseFactory = new Psr17Factory();

        $this->beConstructedWith($properties, $client, $uriFactory, $logger, $responseFactory, $cache);
    }

    protected function getHttpClientMock()
    {
        $callback = static function ($method, $url, $options) {
            $body = '';
            $info = [];

            switch ($url) {
                case 'http://local/cas/serviceValidate?service=service&ticket=ticket&invalid_header=true':
                    $body = <<< 'EOF'
<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas">
 <cas:authenticationSuccess>
  <cas:user>username</cas:user>
  <cas:proxyGrantingTicket>PGTIOU-84678-8a9d...</cas:proxyGrantingTicket>
 </cas:authenticationSuccess>
</cas:serviceResponse>
EOF;
                    $info = [
                        'response_headers' => [
                            'Content-Type' => 'foo/bar',
                        ],
                    ];

                    break;
                case 'http://local/cas/serviceValidate?service=service&ticket=ticket&invalid_ticket=true':
                    $body = <<<'EOF'
<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas">
 <cas:authenticationFailure code="INVALID_TICKET">
    Ticket ST-1856339-aA5Yuvrxzpv8Tau1cYQ7 not recognized
  </cas:authenticationFailure>
</cas:serviceResponse>
EOF;
                    $info = [
                        'response_headers' => [
                            'Content-Type' => 'text/xml',
                        ],
                    ];

                    break;
                case 'http://local/cas/serviceValidate?service=service&ticket=ticket':
                    $body = <<< 'EOF'
<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas">
 <cas:authenticationSuccess>
  <cas:user>username</cas:user>
  <cas:proxyGrantingTicket>PGTIOU-84678-8a9d...</cas:proxyGrantingTicket>
 </cas:authenticationSuccess>
</cas:serviceResponse>
EOF;
                    $info = [
                        'response_headers' => [
                            'Content-Type' => 'text/xml',
                        ],
                    ];

                    break;
                case 'http://local/cas/serviceValidate?service=service&ticket=ticket&http_code=404':
                    $body = '';
                    $info = [
                        'http_code' => 404,
                    ];

                    break;
                case 'http://local/cas/serviceValidate?service=service':
                case 'http://local/cas/serviceValidate?service=&ticket=':
                    $body = '';

                    break;
                case 'http://local/cas/serviceValidate?service=service&ticket=ticket&invalid_xml=true':
                    $body = <<< 'EOF'
<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas">
EOF;
                    $info = [
                        'response_headers' => [
                            'Content-Type' => 'text/xml',
                        ],
                    ];

                    break;
                case 'http://local/cas/serviceValidate?service=service&ticket=ticket&renew=true':
                    $info = [
                        'response_headers' => [
                            'Content-Type' => 'text/xml',
                        ],
                    ];
            }

            return new MockResponse($body, $info);
        };

        return new MockHttpClient($callback);
    }
}
