<?php

namespace App\Services\Admin;

use App\Enums\TenantStatus;
use App\Models\AdminUser;
use App\Models\Tenant;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\Request;

final class UnblockTenant
{
    public function __construct(
        private readonly DatabaseManager $database,
        private readonly AccessLogService $accessLogs,
    ) {}

    public function execute(Tenant $tenant, AdminUser $adminUser, Request $request): Tenant
    {
        return $this->database->transaction(function () use ($tenant, $adminUser, $request): Tenant {
            $lockedTenant = Tenant::query()->whereKey($tenant->getKey())->lockForUpdate()->firstOrFail();

            $lockedTenant->forceFill([
                'status' => TenantStatus::Active,
                'blocked_at' => null,
                'blocked_by_admin_user_id' => null,
                'blocked_reason' => null,
            ])->save();

            $this->accessLogs->record($adminUser, 'tenant.unblocked', $request, $lockedTenant);

            return $lockedTenant;
        });
    }
}
