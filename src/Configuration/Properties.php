<?php

declare(strict_types=1);

namespace drupol\psrcas\Configuration;

use function array_key_exists;

/**
 * Class Properties.
 */
final class Properties implements PropertiesInterface
{
    /**
     * @var array
     */
    private $properties;

    /**
     * Properties constructor.
     *
     * @param array $properties
     */
    public function __construct(array $properties)
    {
        $this->properties = $properties;

        $this->properties += ['protocol' => []];

        foreach (array_keys($this->properties['protocol']) as $key) {
            $this->properties['protocol'][$key] += ['default_parameters' => []];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function all(): array
    {
        return $this->properties;
    }

    /**
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->properties);
    }

    /**
     * @param mixed $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->properties[$offset];
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        $this->properties[$offset] = $value;
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset): void
    {
        unset($this->properties[$offset]);
    }
}
