<?php

declare(strict_types=1);

namespace drupol\psrcas\Service;

use drupol\psrcas\Handler\HandlerInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Interface ServiceInterface.
 */
interface ServiceInterface extends HandlerInterface
{
    /**
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @return \Psr\Http\Message\ResponseInterface|null
     */
    public function getCredentials(ResponseInterface $response): ?ResponseInterface;
}
