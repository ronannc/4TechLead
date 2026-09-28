<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'tenant_id',
    'user_id',
    'approval_id',
    'tool_name',
    'mode',
    'status',
    'arguments',
    'result',
    'error',
    'duration_ms',
])]
class AgentToolCall extends Model
{
    use BelongsToTenant;

    protected function casts(): array
    {
        return [
            'arguments' => 'array',
            'result' => 'array',
            'duration_ms' => 'integer',
        ];
    }
}
