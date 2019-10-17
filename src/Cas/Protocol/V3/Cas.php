<?php

declare(strict_types=1);

namespace drupol\psrcas\Cas\Protocol\V3;

use drupol\psrcas\Cas\AbstractCasProtocol;
use drupol\psrcas\Utils\Uri;
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

        if (Uri::hasParams($uri, 'pgtId') && !Uri::hasParams($uri, 'pgtIou')) {
            $this
                ->getLogger()
                ->debug(
                    'Missing proxy callback parameter (pgtIou).'
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
                    'Missing proxy callback parameter (pgtId).'
                );

            // Todo: Verify what is supposed to return this response.
            return $this
                ->getResponseFactory()
                ->createResponse(200);
        }

        if (!(Uri::hasParams($uri, 'pgtId', 'pgtIou'))) {
            $this
                ->getLogger()
                ->debug(
                    'CAS server just checked the proxy callback endpoint.'
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
            $cacheItem = $this->getCache()->getItem($pgtIou);
        } catch (\Exception $e) {
            $this
                ->getLogger()
                ->error($e->getMessage(), ['exception' => $e]);

            // Todo: Verify what is supposed to return this response.
            return $this
                ->getResponseFactory()
                ->createResponse(200);
        }

        $this
            ->getCache()
            ->save(
                $cacheItem
                    ->set($pgtId)
                    ->expiresAfter(300)
            );

        $this
            ->getLogger()
            ->debug(
                'Storing proxy callback parameters (pgtId and pgtIou)',
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
        array $parameters = []
    ): ?ResponseInterface {
        $parameters += [
            'renew' => false,
            'gateway' => false,
        ];

        if (true === $parameters['gateway'] && true === $parameters['renew']) {
            // Todo log error here.
            return null;
        }

        if ([] === $parameters = $this->formatProtocolParameters($request, $parameters)) {
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
                    $parameters
                )
            );
    }

    /**
     * {@inheritdoc}
     */
    public function logout(
        ServerRequestInterface $request,
        array $parameters = []
    ): ResponseInterface {
        $parameters = $this->formatProtocolParameters($request, $parameters);

        return $this
            ->getResponseFactory()
            ->createResponse(302)
            ->withHeader(
                'Location',
                (string) $this->get(
                    $request->getUri(),
                    'logout',
                    $parameters
                )
            );
    }

    /**
     * {@inheritdoc}
     */
    public function serviceValidate(
        ServerRequestInterface $request,
        array $parameters = []
    ): ?ResponseInterface {
        $parameters = $this->formatProtocolParameters($request, $parameters);

        return $this->validateCasRequest(
            $request
                ->withUri(
                    $this->get(
                        $request->getUri(),
                        'servicevalidate',
                        $parameters
                    )
                )
        );
    }
}
