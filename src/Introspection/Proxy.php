<?php

declare(strict_types=1);

namespace drupol\psrcas\Introspection;

use drupol\psrcas\Introspection\Contract\Proxy as ProxyInterface;

/**
 * Class Proxy.
 */
final class Proxy extends Introspection implements ProxyInterface
{
    /**
     * {@inheritdoc}
     */
    public function getProxyTicket(): string
    {
        return $this->getParsedResponse()['serviceResponse']['proxySuccess']['proxyTicket'];
    }
}
