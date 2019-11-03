<?php

declare(strict_types=1);

namespace drupol\psrcas\Service;

use drupol\psrcas\Introspection\Introspector;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * Class Proxy.
 */
final class Proxy extends Service implements ServiceInterface
{
    /**
     * {@inheritdoc}
     */
    public function getCredentials(ResponseInterface $response): ?ResponseInterface
    {
        try {
            $introspect = Introspector::detect($response);
        } catch (InvalidArgumentException $exception) {
            $this
                ->getLogger()
                ->error($exception->getMessage());

            return null;
        }

        if (false === ($introspect instanceof \drupol\psrcas\Introspection\Contract\Proxy)) {
            return null;
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    protected function getProtocolProperties(): array
    {
        return $this->getProperties()['protocol']['proxy'] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    protected function getUri(): UriInterface
    {
        return $this->buildUri(
            $this->getServerRequest()->getUri(),
            'proxy',
            $this->formatProtocolParameters($this->getParameters())
        );
    }
}
