<?php

namespace App\Policies;

use App\Models\AdminUser;
use App\Models\Tenant;

final class TenantPolicy
{
    public function viewAny(AdminUser $adminUser): bool
    {
        return $adminUser->canManageTenants();
    }

    public function view(AdminUser $adminUser, Tenant $tenant): bool
    {
        return $adminUser->canManageTenants();
    }

    public function block(AdminUser $adminUser, Tenant $tenant): bool
    {
        return $adminUser->canManageTenants();
    }

    public function unblock(AdminUser $adminUser, Tenant $tenant): bool
    {
        return $adminUser->canManageTenants();
    }
}
