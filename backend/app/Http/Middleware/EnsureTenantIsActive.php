<?php

namespace App\Http\Middleware;

use App\Enums\TenantStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureTenantIsActive
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenantStatus = $request->user()?->tenant?->status;

        if ($tenantStatus === TenantStatus::Blocked) {
            return response()->json([
                'message' => 'O acesso deste tenant está bloqueado.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
