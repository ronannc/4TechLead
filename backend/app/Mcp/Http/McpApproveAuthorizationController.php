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
        $authRequest = $request->session()->get('mcp.oauth.authorizations.'.$authToken);

        abort_unless(is_string($authRequest) && $authToken !== '', Response::HTTP_FORBIDDEN);

        $request->session()->put('authToken', $authToken);
        $request->session()->put('authRequest', $authRequest);

        try {
            return $passportController->approve($request, $psrResponse);
        } finally {
            $request->session()->forget('mcp.oauth.authorizations.'.$authToken);
        }
    }
}
