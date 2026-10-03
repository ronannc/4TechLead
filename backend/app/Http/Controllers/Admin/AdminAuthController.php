<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminLoginRequest;
use App\Services\Admin\AuthenticateAdminUser;
use App\Services\Admin\LogoutAdminUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class AdminAuthController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('auth/presentation/pages/Login');
    }

    public function store(AdminLoginRequest $request, AuthenticateAdminUser $authenticate): RedirectResponse
    {
        $authenticate->execute($request->validated(), $request);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request, LogoutAdminUser $logout): RedirectResponse
    {
        $logout->execute($request);

        return redirect()->route('admin.login');
    }
}
