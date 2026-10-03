<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TenantStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BlockTenantRequest;
use App\Models\AdminUser;
use App\Models\Tenant;
use App\Services\Admin\BlockTenant;
use App\Services\Admin\ListTenants;
use App\Services\Admin\UnblockTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class TenantController extends Controller
{
    public function index(Request $request, ListTenants $listTenants): Response
    {
        $this->authorize('viewAny', Tenant::class);

        $filters = $request->validate([
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'nullable', 'string', 'in:active,blocked'],
            'per_page' => ['sometimes', 'integer', 'in:10,25,50,100'],
        ]);

        return Inertia::render('tenants/presentation/pages/Index', [
            'tenants' => $listTenants->execute($filters),
            'filters' => [
                'search' => $filters['search'] ?? '',
                'status' => $filters['status'] ?? '',
            ],
            'statuses' => array_map(
                static fn (TenantStatus $status): array => ['value' => $status->value, 'label' => ucfirst($status->value)],
                TenantStatus::cases(),
            ),
        ]);
    }

    public function show(Tenant $tenant): Response
    {
        $this->authorize('view', $tenant);

        return Inertia::render('tenants/presentation/pages/Show', [
            'tenant' => $tenant->load('blockedByAdminUser:id,name')->loadCount('users'),
            'recentAccesses' => $tenant->accessLogs()
                ->with('actor')
                ->where('event', 'user.login')
                ->latest('created_at')
                ->limit(20)
                ->get()
                ->map(static fn ($accessLog): array => [
                    'id' => $accessLog->id,
                    'user' => $accessLog->actor?->name,
                    'email' => $accessLog->actor?->email,
                    'ip_address' => $accessLog->ip_address,
                    'created_at' => $accessLog->created_at?->toIso8601String(),
                ]),
        ]);
    }

    public function block(BlockTenantRequest $request, Tenant $tenant, BlockTenant $blockTenant): RedirectResponse
    {
        /** @var AdminUser $adminUser */
        $adminUser = $request->user('admin');

        $blockTenant->execute($tenant, $request->validated('reason'), $adminUser, $request);

        return back()->with('success', 'O acesso do tenant foi bloqueado.');
    }

    public function unblock(Request $request, Tenant $tenant, UnblockTenant $unblockTenant): RedirectResponse
    {
        $this->authorize('unblock', $tenant);

        /** @var AdminUser $adminUser */
        $adminUser = $request->user('admin');

        $unblockTenant->execute($tenant, $adminUser, $request);

        return back()->with('success', 'O acesso do tenant foi desbloqueado.');
    }
}
