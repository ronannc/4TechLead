<?php

namespace App\Mcp\Http;

use Illuminate\Http\Request;
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

        if (is_string($authToken) && is_string($authRequest)) {
            $authorizations = $this->request->session()->get('mcp.oauth.authorizations', []);
            $authorizations[$authToken] = $authRequest;
            $this->request->session()->put('mcp.oauth.authorizations', $authorizations);
        }

        return $this;
    }

    public function toResponse($request): Response
    {
        return response()->view('oauth.authorize', $this->parameters);
    }
}
