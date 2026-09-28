<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'id',
    'tenant_id',
    'user_id',
    'tool_name',
    'payload_hash',
    'payload',
    'status',
    'expires_at',
    'approved_at',
    'consumed_at',
    'result',
])]
class AgentApproval extends Model
{
    use BelongsToTenant;

    protected $keyType = 'string';

    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'result' => 'array',
            'expires_at' => 'datetime',
            'approved_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }
}
