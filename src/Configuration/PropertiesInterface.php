<?php

declare(strict_types=1);

namespace drupol\psrcas\Configuration;

use ArrayAccess;

/**
 * Interface PropertiesInterface.
 *
 * @template-extends ArrayAccess<string, mixed>
 */
interface PropertiesInterface extends ArrayAccess
{
    /**
     * @return array<string, mixed>
     */
    public function all(): array;
}
