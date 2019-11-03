<?php

declare(strict_types=1);

namespace drupol\psrcas\Handler;

use drupol\psrcas\Utils\Uri;
use Exception;
use Psr\Http\Message\ResponseInterface;

/**
 * Class ProxyCallback.
 */
final class ProxyCallback extends Handler implements HandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle(): ?ResponseInterface
    {
        $serverRequest = $this->getServerRequest();
        $response = $this
            ->getResponseFactory()
            ->createResponse(200);

        // POST parameters prevails over GET parameters.
        $parameters = $this->getParameters() +
            (array) $serverRequest->getParsedBody() +
            Uri::getParams($serverRequest->getUri()) +
            ['pgtId' => null, 'pgtIou' => null];

        if (null === $parameters['pgtId'] && null === $parameters['pgtIou']) {
            $this
                ->getLogger()
                ->debug(
                    'CAS server just checked the proxy callback endpoint.'
                );

            return $response;
        }

        if (null === $parameters['pgtIou']) {
            $this
                ->getLogger()
                ->debug(
                    'Missing proxy callback parameter (pgtIou).'
                );

            return $response->withStatus(500);
        }

        if (null === $parameters['pgtId']) {
            $this
                ->getLogger()
                ->debug(
                    'Missing proxy callback parameter (pgtId).'
                );

            return $response->withStatus(500);
        }

        try {
            $cacheItem = $this->getCache()->getItem($parameters['pgtIou']);
        } catch (Exception $exception) {
            $this
                ->getLogger()
                ->error($exception->getMessage());

            return $response->withStatus(500);
        }

        $this
            ->getCache()
            ->save(
                $cacheItem
                    ->set($parameters['pgtId'])
                    ->expiresAfter(300)
            );

        $this
            ->getLogger()
            ->debug(
                'Storing proxy callback parameters (pgtId and pgtIou).'
            );

        return $response;
    }
}
