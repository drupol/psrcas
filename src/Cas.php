<?php

declare(strict_types=1);

namespace drupol\psrcas;

use drupol\psrcas\Handler\ProxyCallback;
use drupol\psrcas\Redirect\Login;
use drupol\psrcas\Redirect\Logout;
use drupol\psrcas\Service\Proxy;
use drupol\psrcas\Service\ProxyValidate;
use drupol\psrcas\Service\ServiceValidate;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Cas.
 */
final class Cas extends AbstractCas
{
    /**
     * {@inheritdoc}
     */
    public function handleProxyCallback(
        array $parameters = [],
        ?ResponseInterface $response = null
    ): ?ResponseInterface {
        $proxyCallback = new ProxyCallback(
            $this->getServerRequest(),
            $parameters,
            $this->getProperties(),
            $this->getUriFactory(),
            $this->getResponseFactory(),
            $this->getStreamFactory(),
            $this->getCache(),
            $this->getLogger()
        );

        return $response ?? $proxyCallback->handle();
    }

    /**
     * {@inheritdoc}
     */
    public function login(array $parameters = []): ?ResponseInterface
    {
        $login = new Login(
            $this->getServerRequest(),
            $parameters,
            $this->getProperties(),
            $this->getUriFactory(),
            $this->getResponseFactory(),
            $this->getStreamFactory(),
            $this->getCache(),
            $this->getLogger()
        );

        return $login->handle();
    }

    /**
     * {@inheritdoc}
     */
    public function logout(array $parameters = []): ?ResponseInterface
    {
        $logout = new Logout(
            $this->getServerRequest(),
            $parameters,
            $this->getProperties(),
            $this->getUriFactory(),
            $this->getResponseFactory(),
            $this->getStreamFactory(),
            $this->getCache(),
            $this->getLogger()
        );

        return $logout->handle();
    }

    /**
     * {@inheritdoc}
     */
    public function requestProxyTicket(array $parameters = [], ?ResponseInterface $response = null): ?ResponseInterface
    {
        $proxyRequestService = new Proxy(
            $this->getServerRequest(),
            $parameters,
            $this->getProperties(),
            $this->getHttpClient(),
            $this->getUriFactory(),
            $this->getResponseFactory(),
            $this->getRequestFactory(),
            $this->getStreamFactory(),
            $this->getCache(),
            $this->getLogger()
        );

        if (null === $response) {
            if (null === $response = $proxyRequestService->handle()) {
                $this
                    ->getLogger()
                    ->error('Error during the proxy ticket request.');

                return null;
            }
        }

        $credentials = $proxyRequestService->getCredentials($response);

        if (null === $credentials) {
            $this
                ->getLogger()
                ->error('Unable to authenticate the user.');
        }

        return $credentials;
    }

    /**
     * {@inheritdoc}
     */
    public function requestProxyValidate(
        array $parameters = [],
        ?ResponseInterface $response = null
    ): ?ResponseInterface {
        $proxyValidateService = new ProxyValidate(
            $this->getServerRequest(),
            $parameters,
            $this->getProperties(),
            $this->getHttpClient(),
            $this->getUriFactory(),
            $this->getResponseFactory(),
            $this->getRequestFactory(),
            $this->getStreamFactory(),
            $this->getCache(),
            $this->getLogger()
        );

        if (null === $response) {
            if (null === $response = $proxyValidateService->handle()) {
                $this
                    ->getLogger()
                    ->error('Error during the proxy validate request.');

                return null;
            }
        }

        $credentials = $proxyValidateService->getCredentials($response);

        if (null === $credentials) {
            $this
                ->getLogger()
                ->error('Unable to authenticate the user.');
        }

        return $credentials;
    }

    /**
     * {@inheritdoc}
     */
    public function requestServiceValidate(
        array $parameters = [],
        ?ResponseInterface $response = null
    ): ?ResponseInterface {
        $serviceValidateService = new ServiceValidate(
            $this->getServerRequest(),
            $parameters,
            $this->getProperties(),
            $this->getHttpClient(),
            $this->getUriFactory(),
            $this->getResponseFactory(),
            $this->getRequestFactory(),
            $this->getStreamFactory(),
            $this->getCache(),
            $this->getLogger()
        );

        if (null === $response) {
            if (null === $response = $serviceValidateService->handle()) {
                $this
                    ->getLogger()
                    ->error('Error during the service validate request.');

                return null;
            }
        }

        $credentials = $serviceValidateService->getCredentials($response);

        if (null === $credentials) {
            $this
                ->getLogger()
                ->error('Unable to authenticate the user.');
        }

        return $credentials;
    }
}
