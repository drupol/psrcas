<?php

declare(strict_types=1);

namespace drupol\psrcas\Redirect;

use drupol\psrcas\Handler\Handler;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Redirect.
 */
abstract class Redirect extends Handler
{
    /**
     * @param string $url
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function createRedirectResponse(string $url): ResponseInterface
    {
        $this
            ->getLogger()
            ->debug('Building service response redirection to {url}.', ['url' => $url]);

        return $this
            ->getResponseFactory()
            ->createResponse(302)
            ->withHeader('Location', $url);
    }
}
