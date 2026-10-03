<?php

namespace App\Models;

use App\Enums\McpPromptVersionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'prompt_key',
    'version',
    'content',
    'status',
    'created_by_admin_user_id',
    'published_by_admin_user_id',
    'published_at',
])]
class McpPromptVersion extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => McpPromptVersionStatus::class,
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<AdminUser, $this>
     */
    public function createdByAdminUser(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'created_by_admin_user_id');
    }

    /**
     * @return BelongsTo<AdminUser, $this>
     */
    public function publishedByAdminUser(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'published_by_admin_user_id');
    }
}
