<?php

namespace App\Services\Agent;

use App\Models\AgentApproval;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\UnauthorizedException;
use RuntimeException;

final class AgentApprovalService
{
    private const EXPIRATION_MINUTES = 10;

    /**
     * @param  array<string, mixed>  $payload
     * @return array{approval_id: string, expires_at: string, payload_hash: string}
     */
    public function create(User $user, string $toolName, array $payload): array
    {
        $hash = $this->payloadHash($payload);
        $approval = AgentApproval::query()->create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $user->tenant_id,
            'user_id' => $user->id,
            'tool_name' => $toolName,
            'payload_hash' => $hash,
            'payload' => $payload,
            'status' => 'pending',
            'expires_at' => now()->addMinutes(self::EXPIRATION_MINUTES),
        ]);

        return [
            'approval_id' => $approval->id,
            'expires_at' => $approval->expires_at->toIso8601String(),
            'payload_hash' => $hash,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function consume(User $user, string $approvalId, string $toolName, array $payload): AgentApproval
    {
        return DB::transaction(function () use ($user, $approvalId, $toolName, $payload): AgentApproval {
            $approval = AgentApproval::query()->lockForUpdate()->find($approvalId);

            if (! $approval instanceof AgentApproval
                || $approval->user_id !== $user->id
                || $approval->tenant_id !== $user->tenant_id
                || $approval->tool_name !== $toolName) {
                throw new UnauthorizedException('The approval is invalid for this user or tool.');
            }

            if ($approval->status !== 'pending') {
                throw new RuntimeException('The approval has already been used.');
            }

            if ($approval->expires_at->isPast()) {
                $approval->update(['status' => 'expired']);

                throw new RuntimeException('The approval has expired.');
            }

            if (! hash_equals($approval->payload_hash, $this->payloadHash($payload))) {
                throw new UnauthorizedException('The approved payload does not match the requested change.');
            }

            $approval->update([
                'status' => 'consumed',
                'approved_at' => now(),
                'consumed_at' => now(),
            ]);

            return $approval->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function payloadHash(array $payload): string
    {
        $this->sortRecursively($payload);

        return hash('sha256', (string) json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private function sortRecursively(array &$value): void
    {
        ksort($value);

        foreach ($value as &$item) {
            if (is_array($item)) {
                $this->sortRecursively($item);
            }
        }
    }
}
