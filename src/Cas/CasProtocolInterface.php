<?php

declare(strict_types=1);

namespace drupol\psrcas\Cas;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriFactoryInterface;

/**
 * Interface CasProtocolInterface.
 */
interface CasProtocolInterface
{
    /**
     * Get the http client.
     *
     * @return \Psr\Http\Client\ClientInterface
     *   The http client.
     */
    public function getHttpClient(): ClientInterface;

    /**
     * Get the library properties.
     *
     * @return array
     *   The properties.
     */
    public function getProperties(): array;

    /**
     * Get the URI factory.
     *
     * @return \Psr\Http\Message\UriFactoryInterface
     */
    public function getUriFactory(): UriFactoryInterface;

    /**
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @return bool
     */
    public function isServiceValidateResponseValid(ResponseInterface $response): bool;

    /**
     * If not authenticated, redirect to CAS login.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param null|string $service
     *   The identifier of the application the client is trying to access.
     *   In almost all cases, this will be the URL of the application.
     *   As a HTTP request parameter, this URL value MUST be URL-encoded as
     *   described in section 2.2 of RFC 3986.
     *   If a service is not specified and a single sign-on session does not yet
     *   exist, CAS SHOULD request credentials from the user to initiate a
     *   single sign-on session. If a service is not specified and a single
     *   sign-on session already exists, CAS SHOULD display a message notifying
     *   the client that it is already logged in.
     * @param bool $renew
     *   If this parameter is set, single sign-on will be bypassed. In this
     *   case, CAS will require the client to present credentials regardless of
     *   the existence of a single sign-on session with CAS. This parameter is
     *   not compatible with the gateway parameter. Services redirecting to the
     *   /login URI and login form views posting to the /login URI SHOULD NOT
     *   set both the renew and gateway request parameters. Behavior is
     *   undefined if both are set. It is RECOMMENDED that CAS implementations
     *   ignore the gateway parameter if renew is set. It is RECOMMENDED that
     *   when the renew parameter is set its value be “true”.
     * @param bool $gateway
     *   If this parameter is set, CAS will not ask the client for credentials.
     *   If the client has a pre-existing single sign-on session with CAS, or if
     *   a single sign-on session can be established through non-interactive
     *   means (i.e. trust authentication), CAS MAY redirect the client to the
     *   URL specified by the service parameter, appending a valid service
     *   ticket. (CAS also MAY interpose an advisory page informing the client
     *   that a CAS authentication has taken place.) If the client does not have
     *   a single sign-on session with CAS, and a non-interactive authentication
     *   cannot be established, CAS MUST redirect the client to the URL
     *   specified by the service parameter with no “ticket” parameter appended
     *   to the URL. If the service parameter is not specified and gateway is
     *   set, the behavior of CAS is undefined. It is RECOMMENDED that in this
     *   case, CAS request credentials as if neither parameter was specified.
     *   This parameter is not compatible with the renew parameter. Behavior is
     *   undefined if both are set. It is RECOMMENDED that when the gateway
     *   parameter is set its value be “true”.
     * @param null|string $warn
     *   If this parameter is set, single sign-on MUST NOT be transparent. The
     *   client MUST be prompted before being authenticated to another service.
     * @param null|string $method
     *   The method to be used when sending responses. While native HTTP
     *   redirects (GET) may be utilized as the default method, applications
     *   that require a POST response can use this parameter to indicate the
     *   method type. It is up to the CAS server implementation to determine
     *   whether or not POST responses are supported.
     * @param null|string $username
     *   The username of the client that is trying to log in.
     * @param null|string $password
     *   The password of the client that is trying to log in.
     * @param null|string $lt
     *   A login ticket.
     * @param null|string $rememberMe
     *   If this parameter is set, a Long-Term Ticket Granting Ticket might be
     *   created by the CAS server (refered to as Remember-Me support). It is
     *   subject to the CAS server configuration whether Long-Term Ticket
     *   Granting Tickets are supported or not.
     * @param array $extraParams
     *
     * @return \Psr\Http\Message\ResponseInterface
     *   Null if user is already authenticated, the HTTP Response otherwise.
     */
    public function login(
        ServerRequestInterface $request,
        string $service = null,
        bool $renew = false,
        bool $gateway = false,
        string $warn = null,
        string $method = null,
        string $username = null,
        string $password = null,
        string $lt = null,
        string $rememberMe = null,
        array $extraParams = []
    ): ?ResponseInterface;

    /**
     * Redirect to CAS logout.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param null|string $service
     *   If a service parameter is specified, the browser might be automatically
     *   redirected to the URL specified by service after the logout was
     *   performed by the CAS server. If redirection by the CAS Server is
     *   actually performed depends on the server configuration. As a HTTP
     *   request parameter, the service value MUST be URL-encoded as described
     *   in Section 2.2 of RFC 1738.
     * @param array $extraParams
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function logout(
        ServerRequestInterface $request,
        string $service = null,
        array $extraParams = []
    ): ResponseInterface;

    /**
     * @param \Psr\Http\Message\ResponseInterface $response
     *
     * @return null|\SimpleXMLElement
     */
    public function parseResponse(ResponseInterface $response): ?\SimpleXMLElement;

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param string $service
     *   The identifier of the service for which the ticket was issued. As a
     *   HTTP request parameter, the service value MUST be URL-encoded as
     *   described in Section 2.2 of RFC 1738.
     * @param string $ticket
     *   The service ticket issued by /login.
     * @param null|string $pgtUrl
     *   The URL of the proxy callback. As a HTTP request parameter, the
     *   “pgtUrl” value MUST be URL-encoded as described in Section 2.2 of
     *   RFC 1738.
     * @param bool $renew
     *   If this parameter is set, ticket validation will only succeed if the
     *   service ticket was issued from the presentation of the user’s primary
     *   credentials. It will fail if the ticket was issued from a single
     *   sign-on session.
     * @param null|string $format
     *   If this parameter is set, ticket validation response MUST be produced
     *   based on the parameter value. Supported values are XML and JSON.
     *   If this parameter is not set, the default XML format will be used.
     *   If the parameter value is not supported by the CAS server,
     *   an error code MUST be returned.
     * @param array $extraParams
     *
     * @return null|\Psr\Http\Message\ResponseInterface
     */
    public function serviceValidate(
        ServerRequestInterface $request,
        string $service,
        string $ticket,
        ?string $pgtUrl = null,
        bool $renew = false,
        ?string $format = null,
        array $extraParams = []
    ): ?ResponseInterface;
}
