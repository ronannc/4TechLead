<?php

namespace App\Mcp\Tools;

use App\Mcp\McpAuthenticationResponse;
use App\Models\DevelopmentPlan;
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

#[Description('Proposes or commits a new action inside an existing authorized PDI. Commit requires approval_id from proposal.')]
#[Name('pdi.create_action')]
class PdiCreateAction extends Tool
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'competency' => ['sometimes', 'nullable', 'string', 'max:255'],
            'evidence' => ['sometimes', 'nullable', 'string'],
            'due_date' => ['sometimes', 'nullable', 'date'],
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
            'title' => (string) $data['title'],
            'description' => $data['description'] ?? null,
            'competency' => $data['competency'] ?? null,
            'evidence' => $data['evidence'] ?? null,
            'due_date' => $data['due_date'] ?? null,
        ];

        try {
            $this->authorizePayload($user, $payload);

            if ($mode === 'proposal') {
                $approval = $this->approvalService->create($user, 'pdi.create_action', $payload);
                $result = [
                    'mode' => 'proposal',
                    'requires_confirmation' => true,
                    'approval_id' => $approval['approval_id'],
                    'expires_at' => $approval['expires_at'],
                    'payload_hash' => $approval['payload_hash'],
                    'changes' => [
                        ...$payload,
                        'status' => 'todo',
                        'progress' => 0,
                    ],
                ];
            } else {
                $result = DB::transaction(function () use ($user, $approvalId, $payload): array {
                    $this->approvalService->consume($user, (string) $approvalId, 'pdi.create_action', $payload);

                    $plan = DevelopmentPlan::query()->with('person')->lockForUpdate()->findOrFail($payload['plan_id']);
                    Gate::forUser($user)->authorize('create', DevelopmentPlanItem::class);
                    Gate::forUser($user)->authorize('view', $plan);

                    $action = DevelopmentPlanItem::query()->create([
                        ...collect($payload)->except('plan_id')->all(),
                        'development_plan_id' => $plan->id,
                        'tenant_id' => $user->tenant_id,
                        'status' => 'todo',
                        'progress' => 0,
                    ]);

                    return [
                        'mode' => 'commit',
                        'requires_confirmation' => false,
                        'action' => $this->actionData($action->refresh()),
                    ];
                });
            }

            $this->auditService->record(
                $user,
                'pdi.create_action',
                $mode,
                'succeeded',
                $payload,
                $result,
                null,
                $approvalId,
                (int) round((microtime(true) - $startedAt) * 1000),
            );

            return Response::structured(['data' => $result]);
        } catch (Throwable $exception) {
            $this->auditService->record(
                $user,
                'pdi.create_action',
                $mode,
                'failed',
                $payload,
                null,
                $exception->getMessage(),
                $approvalId,
                (int) round((microtime(true) - $startedAt) * 1000),
            );

            throw $exception;
        }
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'mode' => $schema->string()->description('Use proposal para solicitar confirmação ou commit com approval_id.')->enum(['proposal', 'commit'])->required(),
            'plan_id' => $schema->integer()->description('The existing PDI that will receive the action.')->required(),
            'title' => $schema->string()->description('Action title.')->required(),
            'description' => $schema->string()->description('Optional action description.')->nullable(),
            'competency' => $schema->string()->description('Optional competency related to the action.')->nullable(),
            'evidence' => $schema->string()->description('Optional evidence expected for completion.')->nullable(),
            'due_date' => $schema->string()->description('Optional due date in YYYY-MM-DD format.')->nullable(),
            'approval_id' => $schema->string()->description('Required only for commit, from a matching proposal.')->nullable(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function authorizePayload(User $user, array $payload): void
    {
        $plan = DevelopmentPlan::query()->with('person')->findOrFail($payload['plan_id']);

        Gate::forUser($user)->authorize('create', DevelopmentPlanItem::class);
        Gate::forUser($user)->authorize('view', $plan);
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
