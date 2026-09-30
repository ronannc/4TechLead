<?php

namespace App\Mcp\Tools;

use App\Mcp\McpAuthenticationResponse;
use App\Models\DevelopmentPlanItem;
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

#[Description('Proposes or commits an update to an authorized PDI action. Commit requires approval_id from proposal.')]
#[Name('pdi.update_action')]
class PdiUpdateAction extends Tool
{
    public function __construct(
        private readonly AgentApprovalService $approvalService,
        private readonly AgentToolAuditService $auditService,
    ) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate([
            'mode' => ['required', 'in:proposal,commit'],
            'action_id' => ['required', 'integer', 'min:1'],
            'changes' => ['required', 'array:title,description,competency,evidence,status,due_date,completed_at,progress', 'min:1'],
            'changes.title' => ['sometimes', 'string', 'max:255'],
            'changes.description' => ['sometimes', 'nullable', 'string'],
            'changes.competency' => ['sometimes', 'nullable', 'string', 'max:255'],
            'changes.evidence' => ['sometimes', 'nullable', 'string'],
            'changes.status' => ['sometimes', 'string', 'max:50'],
            'changes.due_date' => ['sometimes', 'nullable', 'date'],
            'changes.completed_at' => ['sometimes', 'nullable', 'date'],
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
            'action_id' => (int) $data['action_id'],
            'changes' => $data['changes'],
        ];

        try {
            $action = DevelopmentPlanItem::query()->with('developmentPlan.person')->findOrFail($payload['action_id']);
            Gate::forUser($user)->authorize('update', $action);

            if ($mode === 'proposal') {
                $approval = $this->approvalService->create($user, 'pdi.update_action', $payload);
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
                    $this->approvalService->consume($user, (string) $approvalId, 'pdi.update_action', $payload);
                    $action = DevelopmentPlanItem::query()->with('developmentPlan.person')->lockForUpdate()->findOrFail($payload['action_id']);
                    Gate::forUser($user)->authorize('update', $action);
                    $action->fill($payload['changes'])->save();

                    return [
                        'mode' => 'commit',
                        'requires_confirmation' => false,
                        'action' => $this->actionData($action->refresh()),
                    ];
                });
            }

            $this->auditService->record($user, 'pdi.update_action', $mode, 'succeeded', $payload, $result, null, $approvalId, (int) round((microtime(true) - $startedAt) * 1000));

            return Response::structured(['data' => $result]);
        } catch (Throwable $exception) {
            $this->auditService->record($user, 'pdi.update_action', $mode, 'failed', $payload, null, $exception->getMessage(), $approvalId, (int) round((microtime(true) - $startedAt) * 1000));

            throw $exception;
        }
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'mode' => $schema->string()->enum(['proposal', 'commit'])->required(),
            'action_id' => $schema->integer()->required(),
            'changes' => $schema->object()->description('Allowed action fields to change: title, description, competency, evidence, status, dates and progress.')->required(),
            'approval_id' => $schema->string()->nullable(),
        ];
    }

    /** @return array<string, mixed> */
    private function actionData(DevelopmentPlanItem $action): array
    {
        return [
            'id' => $action->id,
            'development_plan_id' => $action->development_plan_id,
            'title' => $action->title,
            'description' => $action->description,
            'competency' => $action->competency,
            'evidence' => $action->evidence,
            'status' => $action->status,
            'due_date' => $action->due_date?->toDateString(),
            'completed_at' => $action->completed_at?->toDateString(),
            'progress' => $action->progress,
        ];
    }
}
