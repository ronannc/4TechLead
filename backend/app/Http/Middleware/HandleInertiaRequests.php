<?php

namespace App\Http\Middleware;

use App\Models\AdminUser;
use Inertia\Middleware;
use Illuminate\Http\Request;

final class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'admin';

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        /** @var AdminUser|null $adminUser */
        $adminUser = $request->user('admin');

        return array_merge(parent::share($request), [
            'auth' => [
                'admin' => $adminUser?->only(['id', 'name', 'email', 'role']),
            ],
            'flash' => [
                'success' => fn (): ?string => $request->session()->get('success'),
            ],
        ]);
    }
}
