<?php

namespace App\Mcp\Http;

use Illuminate\Http\Request;
use Laravel\Passport\Http\Controllers\ApproveAuthorizationController;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

class McpApproveAuthorizationController
{
    public function __invoke(
        Request $request,
        ResponseInterface $psrResponse,
        ApproveAuthorizationController $passportController,
    ): Response {
        $authToken = $request->string('auth_token')->toString();
        $authorizations = $request->session()->get('mcp.oauth.authorizations', []);
        $authRequest = is_array($authorizations) ? ($authorizations[$authToken] ?? null) : null;

        if (! is_string($authRequest) && $request->session()->get('authToken') === $authToken) {
            $authRequest = $request->session()->get('authRequest');
        }

        abort_unless(is_string($authRequest) && $authToken !== '', Response::HTTP_FORBIDDEN);

        $request->session()->put('authToken', $authToken);
        $request->session()->put('authRequest', $authRequest);

        try {
            return $passportController->approve($request, $psrResponse);
        } finally {
            $authorizations = $request->session()->get('mcp.oauth.authorizations', []);

            if (is_array($authorizations)) {
                unset($authorizations[$authToken]);
                $request->session()->put('mcp.oauth.authorizations', $authorizations);
            }
        }
    }
}
