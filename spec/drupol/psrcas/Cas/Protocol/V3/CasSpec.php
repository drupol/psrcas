<?php

declare(strict_types=1);

namespace spec\drupol\psrcas\Cas\Protocol\V3;

use drupol\psrcas\Cas\Protocol\V3\Cas;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PhpSpec\ObjectBehavior;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Component\HttpClient\Response\MockResponse;

class CasSpec extends ObjectBehavior
{
    /**
     * @var \Psr\Cache\CacheItemPoolInterface
     */
    protected $cache;

    /**
     * @var \Psr\Cache\CacheItemInterface
     */
    protected $cacheItem;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

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
        $uriFactory = $responseFactory = $streamFactory = new Psr17Factory();

        $this->beConstructedWith($properties, $client, $uriFactory, $logger, $responseFactory, $streamFactory, $cache);

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
                'serviceValidate' => [
                    'path' => '/serviceValidate',
                    'allowed_parameters' => [
                        'ticket',
                        'service',
                    ],
                ],
            ],
        ];

        $client = new Psr18Client($this->getHttpClientMock());
        $uriFactory = $responseFactory = $streamFactory = new Psr17Factory();

        $this->beConstructedWith($properties, $client, $uriFactory, $logger, $responseFactory, $streamFactory, $cache);

        $request = new ServerRequest('GET', 'http://local/cas/serviceValidate?service=service&ticket=ticket&invalid_ticket=true', ['Content-Type' => 'text/xml']);

        $parameters = [
            'service' => 'service',
            'ticket' => 'ticket',
        ];

        $this
            ->serviceValidate($request, $parameters)
            ->shouldBeAnInstanceOf(ResponseInterface::class);

        $this
            ->isServiceValidateResponseValid(
                $this->serviceValidate($request, $parameters)->getWrappedObject()
            )
            ->shouldReturn(false);
    }

    public function it_can_detect_when_gateway_and_renew_are_set_together()
    {
        $from = 'http://local/';

        $request = new ServerRequest('GET', $from);

        $parameters = [
            'renew' => true,
            'gateway' => true,
        ];

        $this
            ->login($request, $parameters)
            ->shouldBeNull();
    }

    public function it_can_detect_wrong_url(LoggerInterface $logger, CacheItemPoolInterface $cache)
    {
        $properties = [
            'base_url' => '',
            'protocol' => [
                'serviceValidate' => [
                    'path' => '\?&!@# // \\ http:// foo bar',
                ],
            ],
        ];

        $client = new Psr18Client($this->getHttpClientMock());
        $uriFactory = $responseFactory = $streamFactory = new Psr17Factory();

        $this->beConstructedWith($properties, $client, $uriFactory, $logger, $responseFactory, $streamFactory, $cache);

        $logger
            ->error('Invalid URL: no "base_uri" option was provided and host or scheme is missing in "%5C%3F&!@%23%20//%20%5C%20http://%20foo%20bar".')
            ->shouldBeCalled();

        $request = new ServerRequest('GET', 'error');

        $parameters = [
            'service' => '',
            'ticket' => '',
        ];

        $this
            ->serviceValidate($request, $parameters)
            ->shouldBeNull();
    }

    public function it_can_gateway_login()
    {
        $from = 'http://local/';

        $request = new ServerRequest('GET', $from);

        $parameters = [
            'renew' => false,
            'gateway' => true,
        ];

        $this
            ->login($request, $parameters)
            ->getHeader('Location')
            ->shouldReturn(['http://local/cas/login?service=http%3A%2F%2Flocal%2F%3Fgateway%3D0']);

        $request = new ServerRequest('GET', $from . '?gateway=false');

        $this
            ->login($request, $parameters)
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

    public function it_can_get_a_stream_factory()
    {
        $this
            ->getStreamFactory()
            ->shouldReturnAnInstanceOf(StreamFactoryInterface::class);
    }

    public function it_can_handle_proxy_callback_request(LoggerInterface $logger, CacheItemPoolInterface $cache, CacheItemInterface $cacheItem)
    {
        $request = new ServerRequest('GET', 'http://local/cas/proxy?pgtId=pgtId&pgtIou=false');

        $this
            ->handleProxyCallback($request)
            ->shouldReturnAnInstanceOf(ResponseInterface::class);

        $this
            ->handleProxyCallback($request)
            ->getStatusCode()
            ->shouldReturn(200);

        $request = new ServerRequest('GET', 'http://local/cas/proxy?pgtIou=pgtIou&pgtId=pgtId');

        $this
            ->handleProxyCallback($request)
            ->shouldReturnAnInstanceOf(ResponseInterface::class);

        $this
            ->handleProxyCallback($request)
            ->getStatusCode()
            ->shouldReturn(200);

        $request = new ServerRequest('GET', 'http://local/cas/proxy?pgtId=pgtId');

        $this
            ->logger
            ->debug('Missing proxy callback parameter (pgtIou).')
            ->shouldBeCalled();

        $this
            ->handleProxyCallback($request)
            ->shouldReturnAnInstanceOf(ResponseInterface::class);

        $this
            ->handleProxyCallback($request)
            ->getStatusCode()
            ->shouldReturn(200);

        $request = new ServerRequest('GET', 'http://local/cas/proxy?pgtIou=pgtIou');

        $this
            ->logger
            ->debug('Missing proxy callback parameter (pgtId).')
            ->shouldBeCalled();

        $this
            ->handleProxyCallback($request)
            ->shouldReturnAnInstanceOf(ResponseInterface::class);

        $this
            ->handleProxyCallback($request)
            ->getStatusCode()
            ->shouldReturn(200);

        $request = new ServerRequest('GET', 'http://local/cas/proxy');

        $this
            ->logger
            ->debug('CAS server just checked the proxy callback endpoint.')
            ->shouldBeCalled();

        $this
            ->handleProxyCallback($request)
            ->shouldReturnAnInstanceOf(ResponseInterface::class);

        $this
            ->handleProxyCallback($request)
            ->getStatusCode()
            ->shouldReturn(200);

        $request = new ServerRequest('GET', 'http://local/cas/proxy?pgtId=pgtId&pgtIou=pgtIou');

        $this
            ->logger
            ->debug('Storing proxy callback parameters (pgtId and pgtIou)', ['pgtId' => 'pgtId', 'pgtIou' => 'pgtIou'])
            ->shouldBeCalled();

        $this->cache
            ->getItem('false')
            ->willThrow(new \InvalidArgumentException('foo'));

        $this
            ->handleProxyCallback($request)
            ->shouldReturnAnInstanceOf(ResponseInterface::class);

        $this
            ->handleProxyCallback($request)
            ->getStatusCode()
            ->shouldReturn(200);
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

        $parameters = [
            'foo' => 'bar',
            'service' => 'http://foo.bar/',
        ];

        $this
            ->login($request, $parameters)
            ->getHeader('Location')
            ->shouldReturn(['http://local/cas/login?service=http%3A%2F%2Ffoo.bar%2F']);

        $parameters = [
            'custom' => 'foo',
        ];

        $this
            ->login($request, $parameters)
            ->getHeader('Location')
            ->shouldReturn(['http://local/cas/login?custom=foo&service=http%3A%2F%2Flocal%2F']);

        $request = new ServerRequest('GET', 'http://local/', ['referer' => 'http://referer/']);

        $parameters = [
            'foo' => 'bar',
            'service' => 'http://foo.bar/',
        ];

        $this
            ->login($request, $parameters)
            ->getHeader('Location')
            ->shouldReturn(['http://local/cas/login?service=http%3A%2F%2Ffoo.bar%2F']);

        $parameters = [
            'custom' => 'foo',
        ];

        $this
            ->login($request, $parameters)
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

        $parameters = [
            'custom' => 'bar',
        ];

        $this
            ->logout($request, $parameters)
            ->getHeader('Location')
            ->shouldReturn(['http://local/cas/logout?custom=bar&service=http%3A%2F%2Flocal%2F']);

        $parameters = [
            'custom' => 'bar',
            'service' => 'http://custom.local/',
        ];

        $this
            ->logout($request, $parameters)
            ->getHeader('Location')
            ->shouldReturn(['http://local/cas/logout?custom=bar&service=http%3A%2F%2Fcustom.local%2F']);

        $request = new ServerRequest('GET', 'http://local/', ['referer' => 'http://referer/']);

        $parameters = [
            'custom' => 'bar',
        ];

        $this
            ->logout($request, $parameters)
            ->getHeader('Location')
            ->shouldReturn(['http://local/cas/logout?custom=bar&service=http%3A%2F%2Freferer%2F']);

        $parameters = [
            'custom' => 'bar',
            'service' => 'http://custom.local/',
        ];

        $this
            ->logout($request, $parameters)
            ->getHeader('Location')
            ->shouldReturn(['http://local/cas/logout?custom=bar&service=http%3A%2F%2Fcustom.local%2F']);

        $parameters = [
            'service' => 'service',
        ];

        $this
            ->logout($request, $parameters)
            ->shouldReturnAnInstanceOf(ResponseInterface::class);
    }

    public function it_can_parse_a_proxy_request_response()
    {
        $url = 'http://local/cas/proxy?targetService=service&pgt=pgt';

        $request = new ServerRequest('GET', $url);

        $response = $this
            ->requestProxyTicket($request)
            ->getWrappedObject();

        $this
            ->parseProxyTicketResponse($response)
            ->shouldReturn('PT-214-A3OoEPNr4Q9kNNuYzmfN8azU31aDUsuW8nk380k7wDExT5PFJpxR1TrNI3q3VGzyDdi0DpZ1LKb8IhPKZKQvavW-8hnfexYjmLCx7qWNsLib1W-DCzzoLVTosAUFzP3XDn5dNzoNtxIXV9KSztF9fYhwHvU0');

        $url .= '&invalid_xml=true';

        $request = new ServerRequest('GET', $url);

        $response = $this
            ->requestProxyTicket($request)
            ->getWrappedObject();

        $this
            ->parseProxyTicketResponse($response)
            ->shouldReturn(null);

        $url = 'http://local/cas/proxy?targetService=service&pgt=pgt&proxy_failure=true';

        $request = new ServerRequest('GET', $url);

        $response = $this
            ->requestProxyTicket($request)
            ->getWrappedObject();

        $this
            ->parseProxyTicketResponse($response)
            ->shouldReturn(null);

        $url = 'http://local/cas/proxy?targetService=service&pgt=pgt&proxy_failure=true&no_pgt=true';

        $request = new ServerRequest('GET', $url);

        $response = $this
            ->requestProxyTicket($request)
            ->getWrappedObject();

        $this
            ->parseProxyTicketResponse($response)
            ->shouldReturn(null);
    }

    public function it_can_renew_login()
    {
        $from = 'http://local/';

        $request = new ServerRequest('GET', $from);

        $parameters = [
            'renew' => true,
        ];

        $this
            ->login($request, $parameters)
            ->getHeader('Location')
            ->shouldReturn(['http://local/cas/login?service=http%3A%2F%2Flocal%2F%3Frenew%3D0']);

        $request = new ServerRequest('GET', $from . '?renew=false');

        $parameters = [
            'renew' => true,
        ];

        $this
            ->login($request, $parameters)
            ->shouldBeNull();
    }

    public function it_can_validate_a_service_ticket(LoggerInterface $logger, CacheItemPoolInterface $cache)
    {
        $properties = [
            'base_url' => '',
            'protocol' => [
                'serviceValidate' => [
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
        $uriFactory = $responseFactory = $streamFactory = new Psr17Factory();

        $this->beConstructedWith($properties, $client, $uriFactory, $logger, $responseFactory, $streamFactory, $cache);

        $request = new ServerRequest('GET', 'http://local/cas/serviceValidate?service=service&ticket=ticket', ['Content-Type' => 'text/xml']);

        $this
            ->serviceValidate($request)
            ->shouldReturnAnInstanceOf(ResponseInterface::class);

        $this
            ->serviceValidate($request)
            ->getStatusCode()
            ->shouldReturn(200);

        $logger
            ->error('')
            ->shouldNotBeCalled();

        $this
            ->serviceValidate($request)
            ->shouldReturnAnInstanceOf(ResponseInterface::class);

        $request = new ServerRequest('GET', 'http://local/cas/serviceValidate?service=service&ticket=ticket&http_code=404');

        $logger
            ->error('Invalid status code (404) for request URI (http://local/cas/serviceValidate?service=service&ticket=ticket&http_code=404).')
            ->shouldBeCalled();

        $this
            ->serviceValidate($request)
            ->shouldBeNull();

        $logger
            ->error('Unable to find the "Content-Type" header in the response.')
            ->shouldBeCalled();

        $request = new ServerRequest('GET', 'http://local/cas/serviceValidate?service=service&ticket=ticket&invalid_xml=true');

        $this
            ->serviceValidate($request)
            ->shouldNotBeNull();

        $logger
            ->error('Invalid status code (404) for request URI (http://local/cas/serviceValidate?service=service&ticket=ticket&http_code=404).')
            ->shouldBeCalled();

        $request = new ServerRequest('GET', 'http://local/cas/serviceValidate?service=service&ticket=ticket&renew=true');

        $this
            ->serviceValidate($request)
            ->shouldReturnAnInstanceOf(ResponseInterface::class);

        $request = new ServerRequest('GET', 'http://local/cas/serviceValidate?service=service&ticket=ticket&renew=0');

        $this
            ->serviceValidate($request)
            ->shouldBeNull();

        $logger
            ->error('Unable to find the "Content-Type" header in the response.')
            ->shouldBeCalled();

        $request = new ServerRequest('POST', 'foo');

        $this
            ->serviceValidate($request)
            ->shouldBeNull();

        $from = 'http://local/cas/serviceValidate?service=service&ticket=ticket&invalid_header=true';

        $request = new ServerRequest('GET', $from);

        $this
            ->serviceValidate($request)
            ->shouldBeAnInstanceOf(ResponseInterface::class);

        $from = 'http://local/cas/serviceValidate?service=service&ticket=ticket&renew=true';

        $request = new ServerRequest('GET', $from);

        $this
            ->serviceValidate($request)
            ->shouldBeAnInstanceOf(ResponseInterface::class);

        $from = 'http://local/cas/serviceValidate?service=service&ticket=ticket&renew=0';

        $request = new ServerRequest('GET', $from);

        $this
            ->serviceValidate($request)
            ->shouldBeNull();

        $from = 'http://local/cas/serviceValidate?service=service&ticket=ticket&renew=false';

        $this
            ->serviceValidate(new ServerRequest('GET', $from))
            ->shouldBeNull();
    }

    public function it_can_verify_if_a_serviceValidate_request_is_valid(LoggerInterface $logger, CacheItemPoolInterface $cache, CacheItemInterface $cacheItem)
    {
        $properties = [
            'base_url' => '',
            'protocol' => [
                'serviceValidate' => [
                    'path' => 'http://local/cas/serviceValidate',
                    'allowed_parameters' => [
                        'service',
                        'ticket',
                        'http_code',
                        'invalid_xml',
                        'with_pgt',
                        'pgt_valid',
                        'pgt_is_not_string',
                    ],
                ],
            ],
        ];

        $cacheItem
            ->set('pgtId')
            ->willReturn($cacheItem);

        $cacheItem
            ->expiresAfter(300)
            ->willReturn($cacheItem);

        $cache
            ->save($cacheItem)
            ->willReturn(true);

        $cache
            ->hasItem('pgtIou')
            ->willReturn(true);

        $cache
            ->hasItem('pgtIouInvalid')
            ->willReturn(false);

        // See: https://github.com/phpspec/prophecy/pull/429
        $cache
            ->hasItem('false')
            ->willThrow(new \InvalidArgumentException('foo'));

        $cache
            ->getItem('pgtIou')
            ->willReturn($cacheItem);

        $client = new Psr18Client($this->getHttpClientMock());
        $uriFactory = $responseFactory = $streamFactory = new Psr17Factory();

        $this->beConstructedWith($properties, $client, $uriFactory, $logger, $responseFactory, $streamFactory, $cache);

        $from = 'http://local/';

        $request = new ServerRequest('GET', $from);

        $parameters = [
            'service' => 'service',
            'ticket' => 'ticket',
        ];

        $this
            ->serviceValidate($request, $parameters)
            ->shouldNotBeNull();

        $this
            ->isServiceValidateResponseValid(
                $this
                    ->serviceValidate($request, $parameters)
                    ->getWrappedObject()
            )
            ->shouldReturn(true);

        $from = 'http://local/cas/serviceValidate?service=service&ticket=ticket&invalid_xml=true';

        $request = new ServerRequest('GET', $from);

        $response = $this
            ->serviceValidate($request, $parameters);

        $response
            ->shouldBeAnInstanceOf(ResponseInterface::class);

        $logger
            ->error('String could not be parsed as XML', ['response' => (string) $response->getWrappedObject()->getBody()])
            ->shouldBeCalled();

        $this
            ->isServiceValidateResponseValid(
                $this
                    ->serviceValidate($request, $parameters)
                    ->getWrappedObject()
            )
            ->shouldReturn(false);

        $from = 'http://local/cas/serviceValidate?service=service&ticket=ticket&invalid_ticket=true';

        $request = new ServerRequest('GET', $from);

        $response = $this
            ->serviceValidate($request, $parameters);

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
                    ->serviceValidate($request, $parameters)
                    ->getWrappedObject()
            )
            ->shouldReturn(false);

        $from = 'http://local/cas/serviceValidate?service=service&ticket=ticket&with_pgt=true&pgt_valid=true';

        $request = new ServerRequest('GET', $from);

        $this
            ->isServiceValidateResponseValid(
                $this
                    ->serviceValidate($request, $parameters)
                    ->getWrappedObject()
            )
            ->shouldReturn(true);

        $from = 'http://local/cas/serviceValidate?service=service&ticket=ticket&with_pgt=true&pgt_valid=false';

        $logger
            ->error('Unable to validate the response because the pgtIou was not found.')
            ->shouldBeCalled();

        $request = new ServerRequest('GET', $from);

        $response = $this
            ->serviceValidate($request, $parameters)
            ->getWrappedObject();

        $this
            ->isServiceValidateResponseValid($response)
            ->shouldReturn(false);

        $from = 'http://local/cas/serviceValidate?service=service&ticket=ticket&with_pgt=true&pgt_valid=false&pgt_is_not_string=true';

        $logger
            ->error('Unable to validate the response because the pgtIou was not found.')
            ->shouldBeCalled();

        $request = new ServerRequest('GET', $from);

        $response = $this
            ->serviceValidate($request, $parameters)
            ->getWrappedObject();

        $this
            ->isServiceValidateResponseValid($response)
            ->shouldReturn(false);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Cas::class);
    }

    public function let(LoggerInterface $logger, CacheItemPoolInterface $cache, CacheItemInterface $cacheItem)
    {
        $this->logger = $logger;
        $this->cache = $cache;
        $this->cacheItem = $cacheItem;

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
                'serviceValidate' => [
                    'path' => '/serviceValidate',
                    'allowed_parameters' => [
                        'ticket',
                        'service',
                        'custom',
                    ],
                ],
                'proxy' => [
                    'path' => '/proxy',
                    'allowed_parameters' => [
                        'targetService',
                        'pgt',
                    ],
                ],
            ],
        ];

        $client = new Psr18Client($this->getHttpClientMock());
        $uriFactory = $responseFactory = $streamFactory = new Psr17Factory();

        $cacheItem
            ->set('pgtId')
            ->willReturn($cacheItem);

        $cacheItem
            ->expiresAfter(300)
            ->willReturn($cacheItem);

        $cache
            ->save($cacheItem)
            ->willReturn(true);

        $cache
            ->getItem('pgtIou')
            ->willReturn($cacheItem);

        $this->beConstructedWith($properties, $client, $uriFactory, $logger, $responseFactory, $streamFactory, $cache);
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
                case 'http://local/cas/proxy?targetService=service&pgt=pgt&invalid_xml=true':
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

                    break;
                case 'http://local/cas/proxy?pgtIou=pgtIou&pgtId=pgtId':
                    break;
                case 'http://local/cas/serviceValidate?service=service&ticket=ticket&with_pgt=true&pgt_valid=true':
                    $body = <<< 'EOF'
<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas">
 <cas:authenticationSuccess>
  <cas:user>username</cas:user>
  <cas:proxyGrantingTicket>pgtIou</cas:proxyGrantingTicket>
 </cas:authenticationSuccess>
</cas:serviceResponse>
EOF;

                    $info = [
                        'response_headers' => [
                            'Content-Type' => 'text/xml',
                        ],
                    ];

                    break;
                case 'http://local/cas/serviceValidate?service=service&ticket=ticket&with_pgt=true&pgt_valid=false':
                    $body = <<< 'EOF'
<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas">
 <cas:authenticationSuccess>
  <cas:user>username</cas:user>
  <cas:proxyGrantingTicket>pgtIouInvalid</cas:proxyGrantingTicket>
 </cas:authenticationSuccess>
</cas:serviceResponse>
EOF;

                    $info = [
                        'response_headers' => [
                            'Content-Type' => 'text/xml',
                        ],
                    ];

                    break;
                case 'http://local/cas/serviceValidate?service=service&ticket=ticket&with_pgt=true&pgt_valid=false&pgt_is_not_string=true':
                    $body = <<< 'EOF'
<cas:serviceResponse xmlns:cas="http://www.yale.edu/tp/cas">
 <cas:authenticationSuccess>
  <cas:user>username</cas:user>
  <cas:proxyGrantingTicket>false</cas:proxyGrantingTicket>
 </cas:authenticationSuccess>
</cas:serviceResponse>
EOF;

                    $info = [
                        'response_headers' => [
                            'Content-Type' => 'text/xml',
                        ],
                    ];

                    break;
                case 'http://local/cas/proxy?targetService=service&pgt=pgt':
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

                    $info = [
                        'response_headers' => [
                            'Content-Type' => 'text/xml',
                        ],
                    ];

                    break;
                case 'http://local/cas/proxy?targetService=service&pgt=pgt&proxy_failure=true':
                    $body = <<< 'EOF'
<?xml version="1.0" encoding="utf-8"?>
<cas:serviceResponse xmlns:cas="https://ecas.ec.europa.eu/cas/schemas"
                     server="ECAS MOCKUP version 4.6.0.20924 - 09/02/2016 - 14:37"
                     date="2019-10-18T12:17:53.069+02:00" version="4.5">
	<cas:proxyFailure>
        Foo.
	</cas:proxyFailure>
</cas:serviceResponse>
EOF;

                    $info = [
                        'response_headers' => [
                            'Content-Type' => 'text/xml',
                        ],
                    ];

                    break;
                case 'http://local/cas/proxy?targetService=service&pgt=pgt&proxy_failure=true&no_pgt=true':
                    $body = <<< 'EOF'
<?xml version="1.0" encoding="utf-8"?>
<cas:serviceResponse xmlns:cas="https://ecas.ec.europa.eu/cas/schemas"
                     server="ECAS MOCKUP version 4.6.0.20924 - 09/02/2016 - 14:37"
                     date="2019-10-18T12:17:53.069+02:00" version="4.5">
	<cas:proxySuccess>
	</cas:proxySuccess>
</cas:serviceResponse>
EOF;

                    $info = [
                        'response_headers' => [
                            'Content-Type' => 'text/xml',
                        ],
                    ];

                    break;
            }

            return new MockResponse($body, $info);
        };

        return new MockHttpClient($callback);
    }
}
