<?php

namespace App\Services\Admin;

use App\Models\AdminUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

final class AuthenticateAdminUser
{
    public function __construct(private readonly AccessLogService $accessLogs) {}

    /**
     * @param  array{email: string, password: string, remember?: bool}  $credentials
     */
    public function execute(array $credentials, Request $request): AdminUser
    {
        $remember = $credentials['remember'] ?? false;
        unset($credentials['remember']);

        if (! Auth::guard('admin')->attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'email' => 'As credenciais informadas não são válidas.',
            ]);
        }

        $request->session()->regenerate();

        /** @var AdminUser $adminUser */
        $adminUser = Auth::guard('admin')->user();

        $this->accessLogs->record($adminUser, 'admin.login', $request);

        return $adminUser;
    }
}
