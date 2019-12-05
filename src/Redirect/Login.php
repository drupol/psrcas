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

        return $this->createRedirectResponse((string) $this->getUri($validatedParameters));
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
            $service = $this->getUriFactory()->createUri($parameters['service']);

            foreach ($parametersToSetToZero as $parameterToSetToZero) {
                $service = Uri::withParam($service, $parameterToSetToZero, '0');
            }

            $parameters['service'] = (string) $service;
        }

        return $parameters;
    }

    /**
     * {@inheritdoc}
     */
    protected function getProtocolProperties(): array
    {
        $protocolProperties = $this->getProperties()['protocol']['login'] ?? [];

        $protocolProperties['default_parameters'] += [
            'service' => (string) $this->getServerRequest()->getUri(),
        ];

        return $protocolProperties;
    }

    /**
     * @param string[] $parameters
     *
     * @return \Psr\Http\Message\UriInterface
     */
    private function getUri(array $parameters = []): UriInterface
    {
        return $this->buildUri(
            $this->getServerRequest()->getUri(),
            'login',
            $parameters
        );
    }

    /**
     * @param string[] $parameters
     *
     * @return string[]|null
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
