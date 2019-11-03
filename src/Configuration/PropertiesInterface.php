<?php

declare(strict_types=1);

namespace drupol\psrcas\Configuration;

use ArrayAccess;

/**
 * Interface PropertiesInterface.
 */
interface PropertiesInterface extends ArrayAccess
{
    /**
     * @return array
     */
    public function all(): array;
}
