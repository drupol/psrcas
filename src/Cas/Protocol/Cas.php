<?php

declare(strict_types=1);

namespace drupol\psrcas\Cas\Protocol;

use drupol\psrcas\Cas\AbstractCasProtocol;
use drupol\psrcas\Utils\Uri;
use Psr\Cache\InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class Cas.
 */
final class Cas extends AbstractCasProtocol
{
    /**
     * {@inheritdoc}
     */
    public function handleProxyCallback(ServerRequestInterface $request): ResponseInterface
    {
        $uri = $request->getUri();

        if (!(Uri::hasParams($uri, 'pgtId', 'pgtIou'))) {
            $this
                ->getLogger()
                ->debug(
                    'CAS server just checked the proxy callback endpoint.',
                    ['request' => $request, 'uri' => (string) $request->getUri()]
                );

            // Todo: Verify what is supposed to return this response.
            return $this
                ->getResponseFactory()
                ->createResponse(200);
        }

        if (Uri::hasParams($uri, 'pgtId') && !Uri::hasParams($uri, 'pgtIou')) {
            $this
                ->getLogger()
                ->debug(
                    'Missing proxy callback parameter (pgtIou).',
                    ['request' => $request, 'uri' => (string) $request->getUri()]
                );

            // Todo: Verify what is supposed to return this response.
            return $this
                ->getResponseFactory()
                ->createResponse(200);
        }

        if (Uri::hasParams($uri, 'pgtIou') && !Uri::hasParams($uri, 'pgtId')) {
            $this
                ->getLogger()
                ->debug(
                    'Missing proxy callback parameter (pgtId).',
                    ['request' => $request, 'uri' => (string) $request->getUri()]
                );

            // Todo: Verify what is supposed to return this response.
            return $this
                ->getResponseFactory()
                ->createResponse(200);
        }

        // Todo: Verify if those parameters must be retrieved in GET or POST.
        $pgtId = Uri::getParam($uri, 'pgtId');
        $pgtIou = Uri::getParam($uri, 'pgtIou');

        try {
            $cacheItem = $this->getCache()->getItem($pgtId);
        } catch (InvalidArgumentException $e) {
            $this
                ->getLogger()
                ->error($e->getMessage(), ['exception' => $e]);

            // Todo: Verify what is supposed to return this response.
            return $this
                ->getResponseFactory()
                ->createResponse(200);
        }

        $cacheItem->set($pgtIou);

        $this
            ->getCache()
            ->save($cacheItem);

        $this
            ->getLogger()
            ->debug(
                'Storing proxy callback parameters (<em>pgtId</em> and <em>pgtIou</em>)',
                ['pgtId' => $pgtId, 'pgtIou' => $pgtIou]
            );

        // Todo: Verify what is supposed to return this response.
        return $this
            ->getResponseFactory()
            ->createResponse(200);
    }

    /**
     * {@inheritdoc}
     */
    public function login(
        ServerRequestInterface $request,
        string $service = null,
        bool $renew = false,
        bool $gateway = false,
        string $warn = null,
        string $method = null,
        string $username = null,
        string $password = null,
        string $lt = null,
        string $rememberMe = null,
        array $extraParams = []
    ): ?ResponseInterface {
        if (true === $gateway && true === $renew) {
            // Todo log error here.
            return null;
        }

        $protocolParams = \compact(
            'service',
            'renew',
            'gateway',
            'warn',
            'method',
            'username',
            'password',
            'lt',
            'rememberMe'
        );

        if ([] === $protocolParams = $this->formatProtocolParameters($request, $protocolParams)) {
            return null;
        }

        return $this
            ->getResponseFactory()
            ->createResponse(302)
            ->withHeader(
                'Location',
                (string) $this->get(
                    $request->getUri(),
                    'login',
                    $extraParams + $protocolParams
                )
            );
    }

    /**
     * {@inheritdoc}
     */
    public function logout(
        ServerRequestInterface $request,
        string $service = null,
        array $extraParams = []
    ): ResponseInterface {
        $protocolParams = \compact(
            'service'
        );

        $protocolParams = $this->formatProtocolParameters($request, $protocolParams);

        return $this
            ->getResponseFactory()
            ->createResponse(302)
            ->withHeader(
                'Location',
                (string) $this->get(
                    $request->getUri(),
                    'logout',
                    $extraParams + $protocolParams
                )
            );
    }

    /**
     * {@inheritdoc}
     */
    public function serviceValidate(
        ServerRequestInterface $request,
        string $service,
        string $ticket,
        string $pgtUrl = null,
        bool $renew = false,
        string $format = null,
        array $extraParams = []
    ): ?ResponseInterface {
        $protocolParams = \compact(
            'service',
            'ticket',
            'pgtUrl',
            'renew',
            'format'
        );

        if ([] === $protocolParams = $this->formatProtocolParameters($request, $protocolParams)) {
            return null;
        }

        return $this->validateCasRequest(
            $request
                ->withUri(
                    $this->get(
                        $request->getUri(),
                        'servicevalidate',
                        $extraParams + $protocolParams
                    )
                )
        );
    }
}
