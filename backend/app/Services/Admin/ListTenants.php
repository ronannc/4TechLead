<?php

namespace App\Services\Admin;

use App\Enums\TenantStatus;
use App\Models\AccessLog;
use App\Models\Tenant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListTenants
{
    /**
     * @param  array{search?: string|null, status?: string|null, per_page?: int|null}  $filters
     * @return LengthAwarePaginator<int, Tenant>
     */
    public function execute(array $filters): LengthAwarePaginator
    {
        return Tenant::query()
            ->withCount('users')
            ->addSelect([
                'last_access_at' => AccessLog::query()
                    ->selectRaw('MAX(created_at)')
                    ->whereColumn('tenant_id', 'tenants.id')
                    ->where('event', 'user.login'),
            ])
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where('name', 'like', '%'.$search.'%');
            })
            ->when($filters['status'] ?? null, function ($query, string $status): void {
                $query->where('status', TenantStatus::from($status));
            })
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 25)
            ->withQueryString();
    }
}
