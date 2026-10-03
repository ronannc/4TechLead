<?php

namespace App\Policies;

use App\Models\AdminUser;
use App\Models\McpPromptVersion;

final class McpPromptVersionPolicy
{
    public function viewAny(AdminUser $adminUser): bool
    {
        return $adminUser->canManageMcpPrompts();
    }

    public function view(AdminUser $adminUser, McpPromptVersion $version): bool
    {
        return $adminUser->canManageMcpPrompts();
    }

    public function create(AdminUser $adminUser): bool
    {
        return $adminUser->canManageMcpPrompts();
    }

    public function publish(AdminUser $adminUser, McpPromptVersion $version): bool
    {
        return $adminUser->canManageMcpPrompts();
    }

    public function restore(AdminUser $adminUser, McpPromptVersion $version): bool
    {
        return $adminUser->canManageMcpPrompts();
    }
}
