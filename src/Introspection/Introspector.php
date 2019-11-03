<?php

declare(strict_types=1);

namespace drupol\psrcas\Introspection;

use drupol\psrcas\Introspection\Contract\IntrospectionInterface;
use drupol\psrcas\Introspection\Contract\IntrospectorInterface;
use drupol\psrcas\Utils\SimpleXml;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;

use const JSON_ERROR_NONE;

/**
 * Class Introspector.
 */
final class Introspector implements IntrospectorInterface
{
    /**
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @return \drupol\psrcas\Introspection\Contract\IntrospectionInterface
     */
    public static function detect(ResponseInterface $response): IntrospectionInterface
    {
        $format = null;

        if (200 !== $response->getStatusCode()) {
            throw new InvalidArgumentException('Unable to detect the response format.');
        }

        if (true === $response->hasHeader('Content-Type')) {
            $header = mb_substr($response->getHeaderLine('Content-Type'), 0, 16);

            switch ($header) {
                case 'application/json':
                    $format = 'JSON';

                    break;
                case 'application/xml':
                    $format = 'XML';

                    break;
            }
        }

        if (null === $format) {
            throw new InvalidArgumentException('Unable to detect the response format.');
        }

        try {
            $data = self::parse($response, $format);
        } catch (InvalidArgumentException $exception) {
            throw new InvalidArgumentException($exception->getMessage());
        }

        if (isset($data['serviceResponse']['authenticationFailure'])) {
            return new AuthenticationFailure($data, $format, $response);
        }

        if (isset($data['serviceResponse']['authenticationSuccess']['user'])) {
            return new ServiceValidate($data, $format, $response);
        }

        if (isset($data['serviceResponse']['proxySuccess']['proxyTicket'])) {
            return new Proxy($data, $format, $response);
        }

        throw new InvalidArgumentException('Unable to find the response type.');
    }

    /**
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param string $format
     *
     * @throws InvalidArgumentException
     *
     * @return array
     */
    public static function parse(ResponseInterface $response, string $format = 'XML'): array
    {
        if ('XML' === $format) {
            $xml = SimpleXml::fromString((string) $response->getBody());

            if (null === $xml) {
                throw new InvalidArgumentException('Unable to parse the response using XML format.');
            }

            return SimpleXml::toArray($xml);
        }

        if ('JSON' === $format) {
            $json = json_decode((string) $response->getBody(), true);

            if (null === $json || JSON_ERROR_NONE !== json_last_error()) {
                throw new InvalidArgumentException('Unable to parse the response using JSON format.');
            }

            return $json;
        }

        throw new InvalidArgumentException('Unsupported format.');
    }
}
