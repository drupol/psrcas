<?php

declare(strict_types=1);

namespace spec\drupol\psrcas\Redirect;

use drupol\psrcas\Redirect\Login;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7Server\ServerRequestCreator;
use PhpSpec\ObjectBehavior;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use spec\drupol\psrcas\Cas;

class LoginSpec extends ObjectBehavior
{
    public function it_can_deal_with_renew_and_gateway_parameters(ServerRequestInterface $serverRequest, CacheItemPoolInterface $cache, LoggerInterface $logger)
    {
        $psr17Factory = new Psr17Factory();

        $parameters = [
            'renew' => true,
            'gateway' => true,
            'service' => 'service',
        ];

        $this->beConstructedWith($serverRequest, $parameters, Cas::getTestProperties(), $psr17Factory, $psr17Factory, $psr17Factory, $cache, $logger);

        $this
            ->handle()
            ->shouldBeNull();

        $logger
            ->error('Unable to get the Login response, gateway and renew parameter cannot be set together.')
            ->shouldHaveBeenCalledOnce();

        $logger
            ->debug(
                'Login parameters are invalid, not redirecting to login page.',
                [
                    'parameters' => [
                        'renew' => true,
                        'gateway' => true,
                        'service' => 'service?gateway=0&renew=0',
                    ],
                    'validatedParameters' => null,
                ]
            )
            ->shouldHaveBeenCalledOnce();
    }

    public function it_can_deal_with_renew_parameter(ServerRequestInterface $serverRequest, CacheItemPoolInterface $cache, LoggerInterface $logger)
    {
        $psr17Factory = new Psr17Factory();

        $serverRequest = new ServerRequest('GET', 'http://app');

        $parameters = [
            'renew' => 'coin',
            'gateway' => false,
        ];

        $this->beConstructedWith($serverRequest, $parameters, Cas::getTestProperties(), $psr17Factory, $psr17Factory, $psr17Factory, $cache, $logger);

        $this
            ->handle()
            ->shouldBeAnInstanceOf(ResponseInterface::class);

        $logger
            ->debug(
                'Building service response redirection to {url}.',
                [
                    'url' => 'http://local/cas/login?renew=true&service=http%3A%2F%2Fapp%3Frenew%3D0',
                ]
            )
            ->shouldHaveBeenCalledOnce();
    }

    public function it_can_get_a_response(CacheItemPoolInterface $cache, LoggerInterface $logger)
    {
        $psr17Factory = new Psr17Factory();
        $creator = new ServerRequestCreator(
            $psr17Factory, // ServerRequestFactory
            $psr17Factory, // UriFactory
            $psr17Factory, // UploadedFileFactory
            $psr17Factory  // StreamFactory
        );
        $this->beConstructedWith($creator->fromGlobals(), [], Cas::getTestProperties(), $psr17Factory, $psr17Factory, $psr17Factory, $cache, $logger);

        $this
            ->handle()
            ->shouldBeAnInstanceOf(ResponseInterface::class);
    }

    public function it_is_initializable(CacheItemPoolInterface $cache, LoggerInterface $logger)
    {
        $psr17Factory = new Psr17Factory();
        $creator = new ServerRequestCreator(
            $psr17Factory, // ServerRequestFactory
            $psr17Factory, // UriFactory
            $psr17Factory, // UploadedFileFactory
            $psr17Factory  // StreamFactory
        );
        $this->beConstructedWith($creator->fromGlobals(), [], Cas::getTestProperties(), $psr17Factory, $psr17Factory, $psr17Factory, $cache, $logger);

        $this->shouldHaveType(Login::class);
    }
}
