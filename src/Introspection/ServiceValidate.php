<?php

declare(strict_types=1);

namespace drupol\psrcas\Introspection;

use drupol\psrcas\Introspection\Contract\ServiceValidate as ServiceValidateInterface;

/**
 * Class ServiceValidate.
 */
final class ServiceValidate extends Introspection implements ServiceValidateInterface
{
    /**
     * {@inheritdoc}
     */
    public function getCredentials(): array
    {
        return $this->getParsedResponse()['serviceResponse']['authenticationSuccess'];
    }

    /**
     * {@inheritdoc}
     */
    public function getProxies(): array
    {
        $hasProxy = isset($this->getParsedResponse()['serviceResponse']['authenticationSuccess']['proxies']);

        return true === $hasProxy ?
            $this->getParsedResponse()['serviceResponse']['authenticationSuccess']['proxies'] :
            [];
    }
}
