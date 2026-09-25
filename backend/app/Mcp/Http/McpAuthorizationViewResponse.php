<?php

namespace App\Mcp\Http;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Laravel\Passport\Contracts\AuthorizationViewResponse;
use Symfony\Component\HttpFoundation\Response;

class McpAuthorizationViewResponse implements AuthorizationViewResponse
{
    /**
     * @var array<string, mixed>
     */
    private array $parameters = [];

    public function __construct(private readonly Request $request) {}

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function withParameters(array $parameters = []): static
    {
        $this->parameters = $parameters;

        $authToken = $parameters['authToken'] ?? null;
        $authRequest = $this->request->session()->get('authRequest');
        $user = $parameters['user'] ?? null;

        if (is_string($authToken) && is_string($authRequest) && $user !== null) {
            Cache::put(
                McpApproveAuthorizationController::cacheKey($authToken),
                [
                    'auth_request' => $authRequest,
                    'user_id' => (string) $user->getAuthIdentifier(),
                ],
                now()->addMinutes(10),
            );
        }

        return $this;
    }

    public function toResponse($request): Response
    {
        return response()->view('mcp.authorize', $this->parameters);
    }
}
