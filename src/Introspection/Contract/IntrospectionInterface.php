<?php

declare(strict_types=1);

namespace drupol\psrcas\Introspection\Contract;

use Psr\Http\Message\ResponseInterface;

/**
 * Interface IntrospectionInterface.
 */
interface IntrospectionInterface
{
    /**
     * @return string
     */
    public function getFormat(): string;

    /**
     * @return array
     */
    public function getParsedResponse(): array;

    /**
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getResponse(): ResponseInterface;
}
