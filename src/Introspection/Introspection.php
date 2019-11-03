<?php

declare(strict_types=1);

namespace drupol\psrcas\Introspection;

use Psr\Http\Message\ResponseInterface;

abstract class Introspection
{
    /**
     * @var string
     */
    private $format;

    /**
     * @var array
     */
    private $parsedResponse;

    /**
     * @var \Psr\Http\Message\ResponseInterface
     */
    private $response;

    /**
     * Introspection constructor.
     *
     * @param array $parsedResponse
     * @param string $format
     * @param \Psr\Http\Message\ResponseInterface $response
     */
    public function __construct(array $parsedResponse, string $format, ResponseInterface $response)
    {
        $this->response = $response;
        $this->parsedResponse = $parsedResponse;
        $this->format = $format;
    }

    /**
     * @return string
     */
    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * @return array
     */
    public function getParsedResponse(): array
    {
        return $this->parsedResponse;
    }

    /**
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
