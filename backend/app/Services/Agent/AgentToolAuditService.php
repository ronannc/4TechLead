<?php

namespace App\Services\Agent;

use App\Models\AgentToolCall;
use App\Models\User;

final class AgentToolAuditService
{
    /**
     * @param  array<string, mixed>|null  $arguments
     * @param  array<string, mixed>|null  $result
     */
    public function record(
        User $user,
        string $toolName,
        ?string $mode,
        string $status,
        ?array $arguments,
        ?array $result,
        ?string $error,
        ?string $approvalId,
        int $durationMs,
    ): AgentToolCall {
        return AgentToolCall::query()->create([
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'approval_id' => $approvalId,
            'tool_name' => $toolName,
            'mode' => $mode,
            'status' => $status,
            'arguments' => $arguments,
            'result' => $result,
            'error' => $error,
            'duration_ms' => $durationMs,
        ]);
    }
}
