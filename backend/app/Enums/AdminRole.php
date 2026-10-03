<?php

namespace App\Enums;

enum AdminRole: string
{
    case SuperAdmin = 'super_admin';
    case TenantManager = 'tenant_manager';
    case McpManager = 'mcp_manager';

    public function canManageTenants(): bool
    {
        return in_array($this, [self::SuperAdmin, self::TenantManager], true);
    }

    public function canManageMcpPrompts(): bool
    {
        return in_array($this, [self::SuperAdmin, self::McpManager], true);
    }

    public function canViewAccessLogs(): bool
    {
        return $this === self::SuperAdmin || $this === self::TenantManager;
    }
}
