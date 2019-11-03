<?php

declare(strict_types=1);

namespace tests\drupol\psrcas\Exception;

use Exception;
use Psr\Http\Client\ClientExceptionInterface;

class TestClientException extends Exception implements ClientExceptionInterface
{
}
