<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class AccessLogController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', AccessLog::class);

        $filters = $request->validate([
            'event' => ['sometimes', 'nullable', 'string', 'max:100'],
            'tenant_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ]);

        $accessLogs = AccessLog::query()
            ->with(['tenant:id,name', 'actor'])
            ->when($filters['event'] ?? null, fn ($query, string $event) => $query->where('event', $event))
            ->when($filters['tenant_id'] ?? null, fn ($query, int $tenantId) => $query->where('tenant_id', $tenantId))
            ->latest('created_at')
            ->paginate(50)
            ->withQueryString()
            ->through(static fn (AccessLog $accessLog): array => [
                'id' => $accessLog->id,
                'event' => $accessLog->event,
                'actor_type' => class_basename($accessLog->actor_type),
                'actor_name' => $accessLog->actor?->name,
                'actor_email' => $accessLog->actor?->email,
                'tenant' => $accessLog->tenant?->only(['id', 'name']),
                'ip_address' => $accessLog->ip_address,
                'metadata' => $accessLog->metadata,
                'created_at' => $accessLog->created_at?->toIso8601String(),
            ]);

        return Inertia::render('access-logs/presentation/pages/Index', [
            'accessLogs' => $accessLogs,
            'filters' => [
                'event' => $filters['event'] ?? '',
                'tenant_id' => $filters['tenant_id'] ?? '',
            ],
        ]);
    }
}
