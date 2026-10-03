<?php

namespace App\Policies;

use App\Models\AdminUser;

final class AccessLogPolicy
{
    public function viewAny(AdminUser $adminUser): bool
    {
        return $adminUser->canViewAccessLogs();
    }
}
