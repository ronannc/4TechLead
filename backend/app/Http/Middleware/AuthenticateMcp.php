<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Passport\Passport;
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
            auth()->shouldUse('sanctum');
            $request->setUserResolver(fn () => auth('sanctum')->user());

            return $next($request);
        }

        if ($this->passportIsConfigured() && auth('api')->check()) {
            auth()->shouldUse('api');
            $request->setUserResolver(fn () => auth('api')->user());

            return $next($request);
        }

        return $this->unauthorizedResponse($request);
    }

    private function passportIsConfigured(): bool
    {
        return $this->passportKeyIsConfigured('public')
            && $this->passportKeyIsConfigured('private');
    }

    private function passportKeyIsConfigured(string $type): bool
    {
        return filled(config("passport.{$type}_key"))
            || is_file(Passport::keyPath("oauth-{$type}.key"));
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
