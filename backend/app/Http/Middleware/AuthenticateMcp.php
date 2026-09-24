<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMcp
{
    /**
     * Authenticate MCP requests with Passport when configured, while keeping
     * Sanctum available for the existing first-party application clients.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth('sanctum')->check()) {
            $request->setUserResolver(fn () => auth('sanctum')->user());

            return $next($request);
        }

        if ($this->passportIsConfigured() && auth('api')->check()) {
            $request->setUserResolver(fn () => auth('api')->user());

            return $next($request);
        }

        return $this->unauthorizedResponse($request);
    }

    private function passportIsConfigured(): bool
    {
        return filled(config('passport.public_key')) && filled(config('passport.private_key'));
    }

    private function unauthorizedResponse(Request $request): JsonResponse
    {
        $metadataUrl = url('/.well-known/oauth-protected-resource/'.ltrim($request->path(), '/'));

        return response()->json([
            'message' => 'Authentication is required to access the MCP server.',
        ], Response::HTTP_UNAUTHORIZED)->withHeaders([
            'WWW-Authenticate' => 'Bearer realm="mcp", resource_metadata="'.$metadataUrl.'", scope="mcp:use"',
        ]);
    }
}
