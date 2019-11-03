<?php

declare(strict_types=1);

namespace drupol\psrcas\Service;

use drupol\psrcas\Utils\Uri;
use Psr\Http\Message\UriInterface;

/**
 * Class ServiceValidate.
 */
final class ServiceValidate extends Service implements ServiceInterface
{
    /**
     * {@inheritdoc}
     */
    protected function getProtocolProperties(): array
    {
        $protocolProperties = $this->getProperties()['protocol']['serviceValidate'] ?? [];

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
            'serviceValidate',
            $this->formatProtocolParameters($this->getParameters())
        );
    }
}
