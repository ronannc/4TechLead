<?php

namespace App\Models;

use App\Enums\AdminRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class AdminUser extends Authenticatable
{
    use Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => AdminRole::class,
        ];
    }

    /**
     * @return MorphMany<AccessLog, $this>
     */
    public function accessLogs(): MorphMany
    {
        return $this->morphMany(AccessLog::class, 'actor');
    }

    public function canManageTenants(): bool
    {
        return $this->role->canManageTenants();
    }

    public function canManageMcpPrompts(): bool
    {
        return $this->role->canManageMcpPrompts();
    }

    public function canViewAccessLogs(): bool
    {
        return $this->role->canViewAccessLogs();
    }
}
