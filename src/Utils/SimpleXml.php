<?php

declare(strict_types=1);

namespace drupol\psrcas\Utils;

use SimpleXMLElement;

use const LIBXML_NOBLANKS;
use const LIBXML_NOCDATA;

/**
 * Class SimpleXml.
 */
final class SimpleXml
{
    /**
     * @param string $data
     *
     * @return SimpleXMLElement|null
     */
    public static function fromString(string $data): ?SimpleXMLElement
    {
        libxml_use_internal_errors(true);

        $parsed = simplexml_load_string(
            $data,
            'SimpleXMLElement',
            LIBXML_NOCDATA | LIBXML_NOBLANKS,
            'cas',
            true
        );

        if (false === $parsed) {
            // todo: Log errors from libxml_get_errors().
            return null;
        }

        return $parsed;
    }

    /**
     * @param SimpleXMLElement $xml
     *
     * @return array[]|null[]|string[]
     */
    public static function toArray(SimpleXMLElement $xml): array
    {
        return [$xml->getName() => self::toArrayRecursive($xml)];
    }

    /**
     * @param SimpleXMLElement $element
     *
     * @return array[]
     */
    private static function toArrayRecursive(SimpleXMLElement $element): ?array
    {
        return array_map(
            static function ($node) {
                return $node instanceof SimpleXMLElement ?
                    self::toArrayRecursive($node) :
                    $node;
            },
            (array) $element
        );
    }
}
