<?php

declare(strict_types=1);

namespace drupol\psrcas\Redirect;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * Class Logout.
 */
final class Logout extends Redirect implements RedirectInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle(): ?ResponseInterface
    {
        return $this->createRedirectResponse((string) $this->getUri());
    }

    /**
     * {@inheritdoc}
     */
    protected function getProtocolProperties(): array
    {
        return $this->getProperties()['protocol']['logout'] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    private function getUri(): UriInterface
    {
        $serverRequest = $this->getServerRequest()->getUri();
        $parameters = $this->formatProtocolParameters($this->getParameters());

        return $this->buildUri($serverRequest, 'logout', $parameters);
    }
}
