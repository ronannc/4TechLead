<?php

namespace App\Services\Admin;

use App\Enums\TenantStatus;
use App\Models\AdminUser;
use App\Models\Tenant;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\Request;

final class BlockTenant
{
    public function __construct(
        private readonly DatabaseManager $database,
        private readonly AccessLogService $accessLogs,
    ) {}

    public function execute(Tenant $tenant, string $reason, AdminUser $adminUser, Request $request): Tenant
    {
        return $this->database->transaction(function () use ($tenant, $reason, $adminUser, $request): Tenant {
            $lockedTenant = Tenant::query()->whereKey($tenant->getKey())->lockForUpdate()->firstOrFail();

            $lockedTenant->forceFill([
                'status' => TenantStatus::Blocked,
                'blocked_at' => now(),
                'blocked_by_admin_user_id' => $adminUser->getKey(),
                'blocked_reason' => $reason,
            ])->save();

            $this->accessLogs->record($adminUser, 'tenant.blocked', $request, $lockedTenant, [
                'reason' => $reason,
            ]);

            return $lockedTenant;
        });
    }
}
