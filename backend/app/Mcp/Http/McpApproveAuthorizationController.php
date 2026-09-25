<?php

namespace App\Mcp\Http;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Laravel\Passport\Bridge\Client;
use Laravel\Passport\Bridge\Scope;
use Laravel\Passport\Bridge\User;
use Laravel\Passport\Http\Controllers\ConvertsPsrResponses;
use Laravel\Passport\Http\Controllers\HandlesOAuthErrors;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

class McpApproveAuthorizationController
{
    use ConvertsPsrResponses;
    use HandlesOAuthErrors;

    public function __invoke(
        Request $request,
        ResponseInterface $psrResponse,
        AuthorizationServer $server,
    ): Response {
        $authToken = $request->string('auth_token')->toString();
        $authorization = $authToken === '' ? null : Cache::pull(self::cacheKey($authToken));
        $user = $request->user('web');

        abort_unless(
            is_array($authorization)
            && $user !== null
            && isset($authorization['user_id'], $authorization['auth_request'])
            && hash_equals((string) $authorization['user_id'], (string) $user->getAuthIdentifier())
            && is_string($authorization['auth_request']),
            Response::HTTP_FORBIDDEN,
        );

        $authRequest = unserialize($authorization['auth_request'], ['allowed_classes' => [
            AuthorizationRequest::class,
            Client::class,
            Scope::class,
            User::class,
        ]]);

        abort_unless($authRequest instanceof AuthorizationRequestInterface, Response::HTTP_FORBIDDEN);

        $authRequest->setAuthorizationApproved(true);

        return $this->withErrorHandling(fn (): Response => $this->convertResponse(
            $server->completeAuthorizationRequest($authRequest, $psrResponse),
        ), $authRequest->getGrantTypeId() === 'implicit');
    }

    public static function cacheKey(string $authToken): string
    {
        return 'mcp:oauth:authorization:'.hash('sha256', $authToken);
    }
}
