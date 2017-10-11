<?php

declare(strict_types=1);

namespace drupol\psrcas\Cas\Protocol\V3;

use drupol\psrcas\Cas\AbstractCasProtocol;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class Cas.
 */
final class Cas extends AbstractCasProtocol
{
    /**
     * {@inheritdoc}
     */
    public function login(
        ServerRequestInterface $request,
        array $parameters = []
    ): ?ResponseInterface {
        $parameters += [
            'renew' => false,
            'gateway' => false,
        ];

        if (true === $parameters['gateway'] && true === $parameters['renew']) {
            // Todo log error here.
            return null;
        }

        if ([] === $parameters = $this->formatProtocolParameters($request, $parameters)) {
            return null;
        }

        return $this
            ->getResponseFactory()
            ->createResponse(302)
            ->withHeader(
                'Location',
                (string) $this->get(
                    $request->getUri(),
                    'login',
                    $parameters
                )
            );
    }

    /**
     * {@inheritdoc}
     */
    public function logout(
        ServerRequestInterface $request,
        array $parameters = []
    ): ResponseInterface {
        $parameters = $this->formatProtocolParameters($request, $parameters);

        return $this
            ->getResponseFactory()
            ->createResponse(302)
            ->withHeader(
                'Location',
                (string) $this->get(
                    $request->getUri(),
                    'logout',
                    $parameters
                )
            );
    }

    /**
     * {@inheritdoc}
     */
    public function serviceValidate(
        ServerRequestInterface $request,
        array $parameters = []
    ): ?ResponseInterface {
        if ([] === $parameters = $this->formatProtocolParameters($request, $parameters)) {
            return null;
        }

        return $this->validateCasRequest(
            $request
                ->withUri(
                    $this->get(
                        $request->getUri(),
                        'servicevalidate',
                        $parameters
                    )
                )
        );
    }
}
