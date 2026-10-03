<?php

namespace App\Services\Auth;

use App\Exceptions\InvalidCredentialsException;
use App\Models\User;
use App\Services\Admin\AccessLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LoginService
{
    public function __construct(private readonly AccessLogService $accessLogs) {}

    /**
     * @param  array{email: string, password: string}  $credentials
     * @return array{user: User, token: string}
     *
     * @throws InvalidCredentialsException
     */
    public function login(array $credentials, Request $request): array
    {
        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password) || $user->tenant?->isBlocked()) {
            throw new InvalidCredentialsException;
        }

        $this->accessLogs->record($user, 'user.login', $request, $user->tenant);

        return [
            'user' => $user,
            'token' => $user->createToken('api')->plainTextToken,
        ];
    }
}
