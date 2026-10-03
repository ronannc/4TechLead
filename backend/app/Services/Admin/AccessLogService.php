<?php

namespace App\Services\Admin;

use App\Models\AccessLog;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

final class AccessLogService
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function record(
        Model $actor,
        string $event,
        Request $request,
        ?Tenant $tenant = null,
        ?array $metadata = null,
    ): AccessLog {
        return AccessLog::query()->create([
            'tenant_id' => $tenant?->getKey(),
            'actor_type' => $actor->getMorphClass(),
            'actor_id' => $actor->getKey(),
            'event' => $event,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => $metadata,
        ]);
    }
}
