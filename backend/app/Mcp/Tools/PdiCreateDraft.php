<?php

namespace App\Mcp\Tools;

use App\Mcp\McpAuthenticationResponse;
use App\Models\DevelopmentPlan;
use App\Models\DevelopmentPlanItem;
use App\Models\Person;
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

#[Description('Proposes or commits a draft development plan for an authorized 4TechLead person. Commit requires approval_id from proposal.')]
#[Name('pdi.create_draft')]
class PdiCreateDraft extends Tool
{
    public function __construct(
        private readonly AgentApprovalService $approvalService,
        private readonly AgentToolAuditService $auditService,
    ) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate([
            'mode' => ['required', 'in:proposal,commit'],
            'person_id' => ['required', 'integer', 'min:1'],
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['sometimes', 'nullable', 'string'],
            'start_date' => ['sometimes', 'nullable', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_date'],
            'target_role' => ['sometimes', 'nullable', 'string', 'max:255'],
            'target_seniority' => ['sometimes', 'nullable', 'string', 'max:50'],
            'actions' => ['sometimes', 'array', 'max:50'],
            'actions.*.title' => ['required', 'string', 'max:255'],
            'actions.*.description' => ['sometimes', 'nullable', 'string'],
            'actions.*.competency' => ['sometimes', 'nullable', 'string', 'max:255'],
            'actions.*.evidence' => ['sometimes', 'nullable', 'string'],
            'actions.*.due_date' => ['sometimes', 'nullable', 'date'],
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
            'person_id' => (int) $data['person_id'],
            'title' => (string) $data['title'],
            'summary' => $data['summary'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'target_role' => $data['target_role'] ?? null,
            'target_seniority' => $data['target_seniority'] ?? null,
            'actions' => collect($data['actions'] ?? [])->map(fn (array $action): array => [
                'title' => $action['title'],
                'description' => $action['description'] ?? null,
                'competency' => $action['competency'] ?? null,
                'evidence' => $action['evidence'] ?? null,
                'due_date' => $action['due_date'] ?? null,
            ])->values()->all(),
        ];

        try {
            $this->authorizePayload($user, $payload);

            if ($mode === 'proposal') {
                $approval = $this->approvalService->create($user, 'pdi.create_draft', $payload);
                $result = [
                    'mode' => 'proposal',
                    'requires_confirmation' => true,
                    'approval_id' => $approval['approval_id'],
                    'expires_at' => $approval['expires_at'],
                    'payload_hash' => $approval['payload_hash'],
                    'changes' => [
                        ...$payload,
                        'status' => 'draft',
                        'progress' => 0,
                    ],
                ];
            } else {
                $result = DB::transaction(function () use ($user, $approvalId, $payload): array {
                    $this->approvalService->consume(
                        $user,
                        (string) $approvalId,
                        'pdi.create_draft',
                        $payload,
                    );

                    $plan = DevelopmentPlan::query()->create([
                        ...collect($payload)->except('actions')->all(),
                        'tenant_id' => $user->tenant_id,
                        'status' => 'draft',
                        'progress' => 0,
                    ]);

                    foreach ($payload['actions'] as $action) {
                        DevelopmentPlanItem::query()->create([
                            ...$action,
                            'development_plan_id' => $plan->id,
                            'tenant_id' => $user->tenant_id,
                            'status' => 'todo',
                            'progress' => 0,
                        ]);
                    }

                    $plan->load('items');

                    return [
                        'mode' => 'commit',
                        'requires_confirmation' => false,
                        'draft' => [
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
                            'actions' => $plan->items->map(fn (DevelopmentPlanItem $action): array => [
                                'id' => $action->id,
                                'title' => $action->title,
                                'description' => $action->description,
                                'competency' => $action->competency,
                                'evidence' => $action->evidence,
                                'status' => $action->status,
                                'due_date' => $action->due_date?->toDateString(),
                                'progress' => $action->progress,
                            ])->values()->all(),
                        ],
                    ];
                });
            }

            $this->auditService->record(
                $user,
                'pdi.create_draft',
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
                'pdi.create_draft',
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
            'mode' => $schema->string()->description('Use proposal to request confirmation or commit with approval_id.')->enum(['proposal', 'commit'])->required(),
            'person_id' => $schema->integer()->description('The person who owns the PDI.')->required(),
            'title' => $schema->string()->description('PDI title.')->required(),
            'summary' => $schema->string()->description('Optional PDI summary.')->nullable(),
            'start_date' => $schema->string()->description('Optional start date in YYYY-MM-DD format.')->nullable(),
            'end_date' => $schema->string()->description('Optional target date in YYYY-MM-DD format.')->nullable(),
            'target_role' => $schema->string()->description('Optional target role.')->nullable(),
            'target_seniority' => $schema->string()->description('Optional target seniority.')->nullable(),
            'actions' => $schema->array()->description('Optional actions to create with the draft PDI.')->items($schema->object([
                'title' => $schema->string()->required(),
                'description' => $schema->string()->nullable(),
                'competency' => $schema->string()->nullable(),
                'evidence' => $schema->string()->nullable(),
                'due_date' => $schema->string()->nullable(),
            ])),
            'approval_id' => $schema->string()->description('Required only for commit, from a matching proposal.')->nullable(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function authorizePayload(User $user, array $payload): void
    {
        Gate::forUser($user)->authorize('create', DevelopmentPlan::class);

        $person = Person::query()
            ->when($user->tenant_id !== null, fn ($query) => $query->where('tenant_id', $user->tenant_id))
            ->findOrFail($payload['person_id']);

        Gate::forUser($user)->authorize('view', $person);
    }
}
