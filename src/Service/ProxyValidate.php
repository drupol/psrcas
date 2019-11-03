<?php

declare(strict_types=1);

namespace drupol\psrcas\Service;

use drupol\psrcas\Utils\Uri;
use Psr\Http\Message\UriInterface;

/**
 * Class ProxyValidate.
 */
final class ProxyValidate extends Service implements ServiceInterface
{
    /**
     * {@inheritdoc}
     */
    protected function getProtocolProperties(): array
    {
        $protocolProperties = $this->getProperties()['protocol']['proxyValidate'] ?? [];

        $protocolProperties['default_parameters'] += [
            'service' => (string) $this->getServerRequest()->getUri(),
            'ticket' => Uri::getParam($this->getServerRequest()->getUri(), 'ticket'),
        ];

        return $protocolProperties;
    }

    /**
     * {@inheritdoc}
     */
    protected function getUri(): UriInterface
    {
        return $this->buildUri(
            $this->getServerRequest()->getUri(),
            'proxyValidate',
            $this->formatProtocolParameters($this->getParameters())
        );
    }
}
