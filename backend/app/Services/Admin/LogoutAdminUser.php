<?php

namespace App\Services\Admin;

use App\Models\AdminUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class LogoutAdminUser
{
    public function __construct(private readonly AccessLogService $accessLogs) {}

    public function execute(Request $request): void
    {
        /** @var AdminUser|null $adminUser */
        $adminUser = Auth::guard('admin')->user();

        if ($adminUser !== null) {
            $this->accessLogs->record($adminUser, 'admin.logout', $request);
        }

        Auth::guard('admin')->logout();
        $request->session()->regenerate();
        $request->session()->regenerateToken();
    }
}
