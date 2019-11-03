<?php

declare(strict_types=1);

namespace drupol\psrcas\Redirect;

use drupol\psrcas\Utils\Uri;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

use function array_key_exists;

/**
 * Class Login.
 */
final class Login extends Redirect implements RedirectInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle(): ?ResponseInterface
    {
        $parameters = $this->formatProtocolParameters($this->getParameters());
        $validatedParameters = $this->validate($parameters);

        if (null === $validatedParameters) {
            $this
                ->getLogger()
                ->debug(
                    'Login parameters are invalid, not redirecting to login page.',
                    [
                        'parameters' => $parameters,
                        'validatedParameters' => $validatedParameters,
                    ]
                );

            return null;
        }

        return $this->createRedirectResponse((string) $this->getUri());
    }

    /**
     * {@inheritdoc}
     */
    protected function formatProtocolParameters(array $parameters): array
    {
        $parameters = parent::formatProtocolParameters($parameters);

        $parametersToSetToZero = [];

        foreach (['gateway', 'renew'] as $queryParameter) {
            if (false === array_key_exists($queryParameter, $parameters)) {
                continue;
            }

            $parameters[$queryParameter] = 'true';
            $parametersToSetToZero[] = $queryParameter;
        }

        if (true === array_key_exists('service', $parameters)) {
            $parameters['service'] = $this->getUriFactory()->createUri((string) $parameters['service']);

            foreach ($parametersToSetToZero as $parameterToSetToZero) {
                $parameters['service'] = Uri::withParam($parameters['service'], $parameterToSetToZero, '0');
            }

            $parameters['service'] = (string) $parameters['service'];
        }

        return $parameters;
    }

    /**
     * {@inheritdoc}
     */
    protected function getProtocolProperties(): array
    {
        return $this->getProperties()['protocol']['login'] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    private function getUri(): UriInterface
    {
        $serverRequest = $this->getServerRequest()->getUri();
        $parameters = $this->formatProtocolParameters($this->getParameters());

        return $this->buildUri($serverRequest, 'login', $parameters);
    }

    /**
     * {@inheritdoc}
     */
    private function validate(array $parameters): ?array
    {
        $uri = $this->getServerRequest()->getUri();

        $renew = $parameters['renew'] ?? false;
        $gateway = $parameters['gateway'] ?? false;

        if ('true' === $renew && 'true' === $gateway) {
            $this
                ->getLogger()
                ->error('Unable to get the Login response, gateway and renew parameter cannot be set together.');

            return null;
        }

        if (true === array_key_exists('service', $parameters)) {
            $parameters['service'] = $this->getUriFactory()->createUri($parameters['service']);
        }

        foreach (['gateway', 'renew'] as $queryParameter) {
            if (false === array_key_exists($queryParameter, $parameters)) {
                continue;
            }

            if ('true' !== Uri::getParam($uri, $queryParameter, 'true')) {
                return null;
            }
        }

        return $parameters;
    }
}
