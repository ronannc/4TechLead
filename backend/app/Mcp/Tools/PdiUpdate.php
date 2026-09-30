<?php

namespace App\Mcp\Tools;

use App\Mcp\McpAuthenticationResponse;
use App\Models\DevelopmentPlan;
use App\Models\User;
use App\Services\Agent\AgentApprovalService;
use App\Services\Agent\AgentToolAuditService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Throwable;

#[Description('Proposes or commits an update to an authorized PDI. Commit requires approval_id from proposal.')]
#[Name('pdi.update')]
class PdiUpdate extends Tool
{
    public function __construct(
        private readonly AgentApprovalService $approvalService,
        private readonly AgentToolAuditService $auditService,
    ) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate([
            'mode' => ['required', 'in:proposal,commit'],
            'plan_id' => ['required', 'integer', 'min:1'],
            'changes' => ['required', 'array:title,summary,status,start_date,end_date,target_role,target_seniority,progress', 'min:1'],
            'changes.title' => ['sometimes', 'string', 'max:255'],
            'changes.summary' => ['sometimes', 'nullable', 'string'],
            'changes.status' => ['sometimes', 'string', 'max:50'],
            'changes.start_date' => ['sometimes', 'nullable', 'date'],
            'changes.end_date' => ['sometimes', 'nullable', 'date'],
            'changes.target_role' => ['sometimes', 'nullable', 'string', 'max:255'],
            'changes.target_seniority' => ['sometimes', 'nullable', 'string', 'max:50'],
            'changes.progress' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'approval_id' => ['required_if:mode,commit', 'nullable', 'uuid'],
        ]);

        $user = $request->user();

        if (! $user instanceof User) {
            return McpAuthenticationResponse::required();
        }

        $startedAt = microtime(true);
        $mode = (string) $data['mode'];
        $approvalId = $data['approval_id'] ?? null;
        $payload = [
            'plan_id' => (int) $data['plan_id'],
            'changes' => $data['changes'],
        ];

        try {
            $plan = DevelopmentPlan::query()->findOrFail($payload['plan_id']);
            Gate::forUser($user)->authorize('update', $plan);

            if ($mode === 'proposal') {
                $approval = $this->approvalService->create($user, 'pdi.update', $payload);
                $result = [
                    'mode' => 'proposal',
                    'requires_confirmation' => true,
                    'approval_id' => $approval['approval_id'],
                    'expires_at' => $approval['expires_at'],
                    'payload_hash' => $approval['payload_hash'],
                    'changes' => $payload,
                ];
            } else {
                $result = DB::transaction(function () use ($user, $approvalId, $payload): array {
                    $this->approvalService->consume($user, (string) $approvalId, 'pdi.update', $payload);
                    $plan = DevelopmentPlan::query()->lockForUpdate()->findOrFail($payload['plan_id']);
                    Gate::forUser($user)->authorize('update', $plan);
                    $plan->fill($payload['changes'])->save();

                    return [
                        'mode' => 'commit',
                        'requires_confirmation' => false,
                        'pdi' => $this->pdiData($plan->refresh()),
                    ];
                });
            }

            $this->auditService->record($user, 'pdi.update', $mode, 'succeeded', $payload, $result, null, $approvalId, (int) round((microtime(true) - $startedAt) * 1000));

            return Response::structured(['data' => $result]);
        } catch (Throwable $exception) {
            $this->auditService->record($user, 'pdi.update', $mode, 'failed', $payload, null, $exception->getMessage(), $approvalId, (int) round((microtime(true) - $startedAt) * 1000));

            throw $exception;
        }
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'mode' => $schema->string()->enum(['proposal', 'commit'])->required(),
            'plan_id' => $schema->integer()->required(),
            'changes' => $schema->object()->description('Allowed PDI fields to change: title, summary, status, dates, target role/seniority and progress.')->required(),
            'approval_id' => $schema->string()->nullable(),
        ];
    }

    /** @return array<string, mixed> */
    private function pdiData(DevelopmentPlan $plan): array
    {
        return [
            'id' => $plan->id,
            'person_id' => $plan->person_id,
            'title' => $plan->title,
            'summary' => $plan->summary,
            'status' => $plan->status,
            'start_date' => $plan->start_date?->toDateString(),
            'end_date' => $plan->end_date?->toDateString(),
            'target_role' => $plan->target_role,
            'target_seniority' => $plan->target_seniority,
            'progress' => $plan->progress,
        ];
    }
}
