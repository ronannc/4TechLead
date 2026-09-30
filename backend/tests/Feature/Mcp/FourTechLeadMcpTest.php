<?php

use App\Enums\DeliveryAssociationConfidence;
use App\Enums\DeliveryMilestoneType;
use App\Enums\DeliveryParticipantRole;
use App\Enums\DeliveryStage;
use App\Enums\UserRole;
use App\Mcp\McpAuthenticationResponse;
use App\Mcp\Prompts\PrepareOneOnOneAgenda;
use App\Mcp\Prompts\PreparePdiPlan;
use App\Mcp\Prompts\PreparePdiUpdate;
use App\Mcp\Prompts\ReviewPdiProgress;
use App\Mcp\Resources\PersonContextResource;
use App\Mcp\Servers\FourTechLeadServer;
use App\Mcp\Tools\KpiGetEvidence;
use App\Mcp\Tools\KpiGetSummary;
use App\Mcp\Tools\OneOnOneCreateDraft;
use App\Mcp\Tools\OneOnOneCreateNote;
use App\Mcp\Tools\OneOnOneGetHistory;
use App\Mcp\Tools\PdiCreateAction;
use App\Mcp\Tools\PdiCreateDraft;
use App\Mcp\Tools\PdiGetOpenActions;
use App\Mcp\Tools\PdiUpdate;
use App\Mcp\Tools\PdiUpdateAction;
use App\Mcp\Tools\PeopleGetContext;
use App\Mcp\Tools\PeopleSearch;
use App\Models\AgentApproval;
use App\Models\AgentToolCall;
use App\Models\DeliveryCase;
use App\Models\DeliveryCaseMilestone;
use App\Models\DeliveryCaseParticipant;
use App\Models\DevelopmentPlan;
use App\Models\DevelopmentPlanItem;
use App\Models\OneOnOneSession;
use App\Models\OneOnOneTemplate;
use App\Models\Person;
use App\Models\PersonOneOnOneNote;
use App\Models\Team;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Agent\AgentApprovalService;
use App\Services\Agent\AgentContextService;
use App\Services\Agent\AgentToolAuditService;
use Laravel\Passport\ClientRepository;

it('exposes the first read-only tools with structured responses', function () {
    $user = User::factory()->create();
    $person = Person::factory()->create(['name' => 'Ada Lovelace']);
    $session = OneOnOneSession::factory()->create([
        'person_id' => $person->id,
        'title' => '1:1 sobre autonomia',
        'held_at' => '2026-09-20',
        'notes' => 'Revisamos decisões técnicas e próximos passos.',
    ]);

    FourTechLeadServer::actingAs($user, 'sanctum')
        ->tool(PeopleGetContext::class, ['person_id' => $person->id])
        ->assertOk()
        ->assertStructuredContent(fn ($json) => $json
            ->where('data.person.id', $person->id)
            ->where('data.person.name', 'Ada Lovelace')
            ->where('data.recent_one_on_ones.0.id', $session->id)
            ->etc());

    FourTechLeadServer::actingAs($user, 'sanctum')
        ->tool(OneOnOneGetHistory::class, [
            'person_id' => $person->id,
            'limit' => 1,
        ])
        ->assertOk()
        ->assertStructuredContent(fn ($json) => $json
            ->where('data.person_id', $person->id)
            ->has('data.sessions', 1)
            ->where('data.sessions.0.title', '1:1 sobre autonomia')
            ->etc());
});

it('publishes stable domain tool names and read-only annotations', function () {
    $searchTool = new PeopleSearch(app(AgentContextService::class));
    $peopleTool = new PeopleGetContext(app(AgentContextService::class));
    $historyTool = new OneOnOneGetHistory(app(AgentContextService::class));
    $pdiTool = new PdiGetOpenActions(app(AgentContextService::class));
    $kpiTool = new KpiGetSummary(app(AgentContextService::class));
    $evidenceTool = new KpiGetEvidence(app(AgentContextService::class));
    $createNoteTool = new OneOnOneCreateNote(app(AgentApprovalService::class), app(AgentToolAuditService::class));
    $createPdiTool = new PdiCreateDraft(app(AgentApprovalService::class), app(AgentToolAuditService::class));
    $createActionTool = new PdiCreateAction(app(AgentApprovalService::class), app(AgentToolAuditService::class));
    $createDocumentTool = new OneOnOneCreateDraft(app(AgentApprovalService::class), app(AgentToolAuditService::class));
    $updatePdiTool = new PdiUpdate(app(AgentApprovalService::class), app(AgentToolAuditService::class));
    $updateActionTool = new PdiUpdateAction(app(AgentApprovalService::class), app(AgentToolAuditService::class));

    expect($searchTool->toArray())
        ->name->toBe('people.search')
        ->annotations->toHaveKey('readOnlyHint', true);

    expect($peopleTool->toArray())
        ->name->toBe('people.get_context')
        ->annotations->toHaveKey('readOnlyHint', true);

    expect($historyTool->toArray())
        ->name->toBe('one_on_one.get_history')
        ->annotations->toHaveKey('readOnlyHint', true);

    expect($pdiTool->toArray())
        ->name->toBe('pdi.get_open_actions')
        ->annotations->toHaveKey('readOnlyHint', true);

    expect($kpiTool->toArray())
        ->name->toBe('kpi.get_summary')
        ->annotations->toHaveKey('readOnlyHint', true);

    expect($evidenceTool->toArray())
        ->name->toBe('kpi.get_evidence')
        ->annotations->toHaveKey('readOnlyHint', true);

    expect($createNoteTool->toArray())
        ->name->toBe('one_on_one.create_note')
        ->annotations->not->toHaveKey('readOnlyHint');

    expect($createPdiTool->toArray())
        ->name->toBe('pdi.create_draft')
        ->annotations->not->toHaveKey('readOnlyHint');

    expect($createActionTool->toArray())
        ->name->toBe('pdi.create_action')
        ->annotations->not->toHaveKey('readOnlyHint');

    expect($createDocumentTool->toArray())
        ->name->toBe('one_on_one.create_draft')
        ->annotations->not->toHaveKey('readOnlyHint');

    expect($updatePdiTool->toArray())
        ->name->toBe('pdi.update')
        ->annotations->not->toHaveKey('readOnlyHint');

    expect($updateActionTool->toArray())
        ->name->toBe('pdi.update_action')
        ->annotations->not->toHaveKey('readOnlyHint');
});

it('registers and reads the authorized person context resource', function () {
    $user = User::factory()->create();
    $person = Person::factory()->create(['name' => 'Ada Lovelace']);

    FourTechLeadServer::actingAs($user, 'sanctum')
        ->resource(PersonContextResource::class, ['person_id' => $person->id])
        ->assertSee('Ada Lovelace')
        ->assertDontSee('raw_payload');
});

it('registers the one-on-one agenda prompt with authorized tool guidance', function () {
    FourTechLeadServer::prompt(PrepareOneOnOneAgenda::class, [
        'person_id' => 42,
        'focus' => 'autonomia',
    ])
        ->assertSee('people.get_context')
        ->assertSee('autonomia')
        ->assertSee('confirmação explícita');
});

it('registers the PDI prompts with separate planning and update guidance', function () {
    FourTechLeadServer::prompt(PreparePdiPlan::class, [
        'person_id' => 42,
        'focus' => 'autonomia técnica',
        'period_months' => 6,
    ])
        ->assertSee('pdi.get_open_actions')
        ->assertSee('autonomia técnica')
        ->assertSee('não crie ou altere registros');

    FourTechLeadServer::prompt(ReviewPdiProgress::class, [
        'person_id' => 42,
        'focus' => 'evidências',
    ])
        ->assertSee('Revise o progresso')
        ->assertSee('não altere nenhum registro')
        ->assertSee('evidências');

    FourTechLeadServer::prompt(PreparePdiUpdate::class, [
        'person_id' => 42,
        'plan_id' => 7,
        'requested_changes' => 'Ajustar o prazo da ação de arquitetura.',
    ])
        ->assertSee('pdi.update')
        ->assertSee('approval_id')
        ->assertSee('Ajustar o prazo da ação de arquitetura.');
});

it('proposes and commits an inactive draft 1:1 document', function () {
    $user = User::factory()->create();
    $arguments = [
        'mode' => 'proposal',
        'title' => '1:1 mensal de desenvolvimento',
        'description' => 'Pauta para conversas de desenvolvimento.',
        'questions' => [
            'Qual foi o principal avanço desde o último encontro?',
            'Que apoio você precisa?',
        ],
    ];

    $proposal = FourTechLeadServer::actingAs($user, 'sanctum')
        ->tool(OneOnOneCreateDraft::class, $arguments)
        ->assertOk();
    $approvalId = null;
    $proposal->assertStructuredContent(function ($json) use (&$approvalId): void {
        $approvalId = data_get($json->toArray(), 'data.approval_id');
        $json->etc();
    });

    FourTechLeadServer::actingAs($user, 'sanctum')
        ->tool(OneOnOneCreateDraft::class, [
            ...$arguments,
            'mode' => 'commit',
            'approval_id' => $approvalId,
        ])
        ->assertOk()
        ->assertStructuredContent(fn ($json) => $json
            ->where('data.draft.title', '1:1 mensal de desenvolvimento')
            ->where('data.draft.active', false)
            ->where('data.draft.is_default', false)
            ->has('data.draft.questions', 2)
            ->etc());

    expect(OneOnOneTemplate::query()
        ->where('title', '1:1 mensal de desenvolvimento')
        ->value('active'))->toBeFalse();
});

it('proposes and commits a draft PDI with server-controlled draft fields', function () {
    $user = User::factory()->create();
    $person = Person::factory()->create();
    $arguments = [
        'mode' => 'proposal',
        'person_id' => $person->id,
        'title' => 'PDI autonomia técnica',
        'summary' => 'Desenvolver autonomia em decisões de arquitetura.',
        'start_date' => '2026-09-28',
        'end_date' => '2026-12-20',
        'target_role' => 'Tech Lead',
        'target_seniority' => 'senior',
        'actions' => [
            [
                'title' => 'Conduzir uma decisão arquitetural',
                'description' => 'Registrar contexto, alternativas e decisão.',
                'competency' => 'Autonomia',
                'due_date' => '2026-10-30',
            ],
        ],
    ];

    $proposal = FourTechLeadServer::actingAs($user, 'sanctum')
        ->tool(PdiCreateDraft::class, $arguments)
        ->assertOk();
    $approvalId = null;
    $proposal->assertStructuredContent(function ($json) use (&$approvalId): void {
        $approvalId = data_get($json->toArray(), 'data.approval_id');
        $json->etc();
    });

    FourTechLeadServer::actingAs($user, 'sanctum')
        ->tool(PdiCreateDraft::class, [
            ...$arguments,
            'mode' => 'commit',
            'approval_id' => $approvalId,
        ])
        ->assertOk()
        ->assertStructuredContent(fn ($json) => $json
            ->where('data.draft.title', 'PDI autonomia técnica')
            ->where('data.draft.status', 'draft')
            ->where('data.draft.progress', 0)
            ->where('data.draft.person_id', $person->id)
            ->has('data.draft.actions', 1)
            ->where('data.draft.actions.0.status', 'todo')
            ->etc());

    expect(DevelopmentPlan::query()->where('title', 'PDI autonomia técnica')->count())->toBe(1)
        ->and(DevelopmentPlan::query()->where('title', 'PDI autonomia técnica')->value('status'))->toBe('draft')
        ->and(DevelopmentPlanItem::query()->where('title', 'Conduzir uma decisão arquitetural')->count())->toBe(1);
});

it('updates a PDI and one of its actions through approved MCP commits', function () {
    $user = User::factory()->create();
    $person = Person::factory()->create();
    $plan = DevelopmentPlan::factory()->create(['person_id' => $person->id, 'status' => 'draft']);
    $action = DevelopmentPlanItem::factory()->create([
        'development_plan_id' => $plan->id,
        'status' => 'todo',
    ]);

    $propose = function (string $tool, array $arguments) use ($user): string {
        $response = FourTechLeadServer::actingAs($user, 'sanctum')
            ->tool($tool, ['mode' => 'proposal', ...$arguments])
            ->assertOk();
        $approvalId = null;
        $response->assertStructuredContent(function ($json) use (&$approvalId): void {
            $approvalId = data_get($json->toArray(), 'data.approval_id');
            $json->etc();
        });

        return (string) $approvalId;
    };

    $planApproval = $propose(PdiUpdate::class, [
        'plan_id' => $plan->id,
        'changes' => ['title' => 'PDI atualizado'],
    ]);

    FourTechLeadServer::actingAs($user, 'sanctum')
        ->tool(PdiUpdate::class, [
            'mode' => 'commit',
            'plan_id' => $plan->id,
            'changes' => ['title' => 'PDI atualizado'],
            'approval_id' => $planApproval,
        ])
        ->assertOk();

    $actionApproval = $propose(PdiUpdateAction::class, [
        'action_id' => $action->id,
        'changes' => ['status' => 'doing', 'progress' => 40],
    ]);

    FourTechLeadServer::actingAs($user, 'sanctum')
        ->tool(PdiUpdateAction::class, [
            'mode' => 'commit',
            'action_id' => $action->id,
            'changes' => ['status' => 'doing', 'progress' => 40],
            'approval_id' => $actionApproval,
        ])
        ->assertOk()
        ->assertStructuredContent(fn ($json) => $json
            ->where('data.action.status', 'doing')
            ->where('data.action.progress', 40)
            ->etc());

    expect($plan->fresh()->title)->toBe('PDI atualizado')
        ->and($action->fresh()->status)->toBe('doing')
        ->and($action->fresh()->progress)->toBe(40);
});

it('creates an action inside an existing PDI through an approved MCP commit', function () {
    $user = User::factory()->create();
    $person = Person::factory()->create();
    $plan = DevelopmentPlan::factory()->create(['person_id' => $person->id, 'status' => 'draft']);
    $arguments = [
        'mode' => 'proposal',
        'plan_id' => $plan->id,
        'title' => 'Apresentar uma decisão arquitetural',
        'description' => 'Documentar alternativas, trade-offs e decisão.',
        'competency' => 'Autonomia técnica',
        'evidence' => 'Registro da decisão revisado pelo time.',
        'due_date' => '2026-11-15',
    ];

    $proposal = FourTechLeadServer::actingAs($user, 'sanctum')
        ->tool(PdiCreateAction::class, $arguments)
        ->assertOk();
    $approvalId = null;
    $proposal->assertStructuredContent(function ($json) use (&$approvalId, $plan): void {
        $approvalId = data_get($json->toArray(), 'data.approval_id');
        $json
            ->where('data.changes.plan_id', $plan->id)
            ->where('data.changes.status', 'todo')
            ->where('data.changes.progress', 0)
            ->etc();
    });

    FourTechLeadServer::actingAs($user, 'sanctum')
        ->tool(PdiCreateAction::class, [
            ...$arguments,
            'mode' => 'commit',
            'approval_id' => $approvalId,
        ])
        ->assertOk()
        ->assertStructuredContent(fn ($json) => $json
            ->where('data.action.development_plan_id', $plan->id)
            ->where('data.action.title', 'Apresentar uma decisão arquitetural')
            ->where('data.action.status', 'todo')
            ->where('data.action.progress', 0)
            ->etc());

    expect(DevelopmentPlan::query()->count())->toBe(1)
        ->and(DevelopmentPlanItem::query()->where('development_plan_id', $plan->id)->count())->toBe(1)
        ->and(DevelopmentPlanItem::query()->where('title', 'Apresentar uma decisão arquitetural')->value('status'))->toBe('todo');
});

it('proposes and commits a 1:1 note only with a matching one-time approval', function () {
    $user = User::factory()->create();
    $person = Person::factory()->create(['name' => 'Ada Lovelace']);
    $arguments = [
        'mode' => 'proposal',
        'person_id' => $person->id,
        'title' => 'Acompanhar autonomia',
        'body' => 'Revisar decisões técnicas no próximo encontro.',
        'occurred_at' => '2026-09-26',
    ];

    $proposal = FourTechLeadServer::actingAs($user, 'sanctum')
        ->tool(OneOnOneCreateNote::class, $arguments)
        ->assertOk();

    $approvalId = null;
    $proposal->assertStructuredContent(function ($json) use (&$approvalId): void {
        $approvalId = data_get($json->toArray(), 'data.approval_id');
        $json->etc();
    });

    expect($approvalId)->toBeString()
        ->and(PersonOneOnOneNote::query()->count())->toBe(0)
        ->and(AgentApproval::query()->whereKey($approvalId)->value('status'))->toBe('pending')
        ->and(AgentToolCall::query()->where('status', 'succeeded')->count())->toBe(1);

    FourTechLeadServer::actingAs($user, 'sanctum')
        ->tool(OneOnOneCreateNote::class, [
            ...$arguments,
            'mode' => 'commit',
            'approval_id' => $approvalId,
        ])
        ->assertOk()
        ->assertStructuredContent(fn ($json) => $json
            ->where('data.mode', 'commit')
            ->where('data.note.title', 'Acompanhar autonomia')
            ->where('data.note.person_id', $person->id)
            ->etc());

    expect(PersonOneOnOneNote::query()->count())->toBe(1)
        ->and(AgentApproval::query()->whereKey($approvalId)->value('status'))->toBe('consumed')
        ->and(AgentToolCall::query()->where('status', 'succeeded')->count())->toBe(2);

    FourTechLeadServer::actingAs($user, 'sanctum')
        ->tool(OneOnOneCreateNote::class, [
            ...$arguments,
            'mode' => 'commit',
            'approval_id' => $approvalId,
        ])
        ->assertHasErrors(['The approval has already been used.']);

    expect(PersonOneOnOneNote::query()->count())->toBe(1)
        ->and(AgentToolCall::query()->where('status', 'failed')->count())->toBe(1);
});

it('rejects a commit when the approved 1:1 note payload changes', function () {
    $user = User::factory()->create();
    $person = Person::factory()->create();

    $proposal = FourTechLeadServer::actingAs($user, 'sanctum')
        ->tool(OneOnOneCreateNote::class, [
            'mode' => 'proposal',
            'person_id' => $person->id,
            'title' => 'Título aprovado',
        ])
        ->assertOk();
    $approvalId = null;
    $proposal->assertStructuredContent(function ($json) use (&$approvalId): void {
        $approvalId = data_get($json->toArray(), 'data.approval_id');
        $json->etc();
    });

    FourTechLeadServer::actingAs($user, 'sanctum')
        ->tool(OneOnOneCreateNote::class, [
            'mode' => 'commit',
            'person_id' => $person->id,
            'title' => 'Título alterado',
            'approval_id' => $approvalId,
        ])
        ->assertHasErrors(['The approved payload does not match the requested change.']);

    expect(PersonOneOnOneNote::query()->count())->toBe(0)
        ->and(AgentApproval::query()->whereKey($approvalId)->value('status'))->toBe('pending');
});

it('returns normalized KPI evidence without raw webhook payloads through MCP', function () {
    $tenant = Tenant::factory()->create();
    $team = Team::factory()->create(['tenant_id' => $tenant->id]);
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $person = Person::factory()->create(['tenant_id' => $tenant->id, 'team_id' => $team->id]);
    $deliveryCase = DeliveryCase::factory()->create([
        'tenant_id' => $tenant->id,
        'current_stage' => DeliveryStage::Published,
        'story_points' => 5,
        'completed_at' => '2026-09-13 12:00:00',
        'last_activity_at' => '2026-09-13 12:00:00',
    ]);
    DeliveryCaseParticipant::factory()->forDeliveryCase($deliveryCase)->create([
        'person_id' => $person->id,
        'role' => DeliveryParticipantRole::Implementer,
        'confidence' => DeliveryAssociationConfidence::High,
    ]);
    DeliveryCaseMilestone::factory()->forDeliveryCase($deliveryCase)->create([
        'milestone_type' => DeliveryMilestoneType::Published,
        'semantic_key' => 'published:'.$deliveryCase->task_ref,
        'occurred_at' => '2026-09-13 12:00:00',
        'metadata' => ['source' => 'normalized'],
    ]);

    FourTechLeadServer::actingAs($user, 'sanctum')
        ->tool(KpiGetEvidence::class, [
            'person_id' => $person->id,
            'period_start' => '2026-09-13',
            'period_end' => '2026-09-13',
            'limit' => 1,
        ])
        ->assertOk()
        ->assertStructuredContent(fn ($json) => $json
            ->where('data.definition_version', 'delivery-kpis.v1')
            ->where('data.evidence_count', 1)
            ->where('data.cases.0.task_ref', $deliveryCase->task_ref)
            ->where('data.cases.0.participants.0.person_id', $person->id)
            ->where('data.cases.0.milestones.0.milestone_type', 'published')
            ->missing('data.cases.0.raw_payload')
            ->etc());
});

it('returns tenant and person scoped delivery KPIs through MCP', function () {
    $tenant = Tenant::factory()->create();
    $team = Team::factory()->create(['tenant_id' => $tenant->id]);
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $person = Person::factory()->create(['tenant_id' => $tenant->id, 'team_id' => $team->id]);
    $deliveryCase = DeliveryCase::factory()->create([
        'tenant_id' => $tenant->id,
        'current_stage' => DeliveryStage::Published,
        'story_points' => 5,
        'completed_at' => '2026-09-13 12:00:00',
        'last_activity_at' => '2026-09-13 12:00:00',
    ]);
    DeliveryCaseParticipant::factory()->forDeliveryCase($deliveryCase)->create([
        'person_id' => $person->id,
        'role' => DeliveryParticipantRole::Implementer,
        'confidence' => DeliveryAssociationConfidence::High,
    ]);
    DeliveryCaseMilestone::factory()->forDeliveryCase($deliveryCase)->create([
        'milestone_type' => DeliveryMilestoneType::Published,
        'semantic_key' => 'published:'.$deliveryCase->task_ref,
        'occurred_at' => '2026-09-13 12:00:00',
    ]);

    FourTechLeadServer::actingAs($user, 'sanctum')
        ->tool(KpiGetSummary::class, [
            'person_id' => $person->id,
            'period_start' => '2026-09-13',
            'period_end' => '2026-09-13',
        ])
        ->assertOk()
        ->assertStructuredContent(fn ($json) => $json
            ->where('data.definition_version', 'delivery-kpis.v1')
            ->where('data.filters.tenant_id', $tenant->id)
            ->where('data.filters.person_id', $person->id)
            ->where('data.published_delivery_count.value', 1)
            ->where('data.published_story_points.value', 5)
            ->etc());
});

it('does not allow members to query delivery KPIs through MCP', function () {
    $member = User::factory()->member()->create();

    FourTechLeadServer::actingAs($member, 'sanctum')
        ->tool(KpiGetSummary::class)
        ->assertHasErrors(['This action is unauthorized.']);
});

it('returns open PDI actions grouped by active plan through MCP', function () {
    $user = User::factory()->create();
    $person = Person::factory()->create();
    $activePlan = DevelopmentPlan::factory()->create([
        'person_id' => $person->id,
        'status' => 'active',
        'title' => 'PDI autonomia',
    ]);
    DevelopmentPlanItem::factory()->create([
        'development_plan_id' => $activePlan->id,
        'title' => 'Registrar decisões técnicas',
        'status' => 'doing',
    ]);
    DevelopmentPlanItem::factory()->create([
        'development_plan_id' => $activePlan->id,
        'title' => 'Ação concluída',
        'status' => 'done',
    ]);
    $completedPlan = DevelopmentPlan::factory()->create([
        'person_id' => $person->id,
        'status' => 'completed',
    ]);
    DevelopmentPlanItem::factory()->create([
        'development_plan_id' => $completedPlan->id,
        'status' => 'doing',
    ]);

    FourTechLeadServer::actingAs($user, 'sanctum')
        ->tool(PdiGetOpenActions::class, ['person_id' => $person->id])
        ->assertOk()
        ->assertStructuredContent(fn ($json) => $json
            ->where('data.person_id', $person->id)
            ->has('data.plans', 1)
            ->where('data.plans.0.title', 'PDI autonomia')
            ->has('data.plans.0.actions', 1)
            ->where('data.plans.0.actions.0.title', 'Registrar decisões técnicas')
            ->etc());
});

it('limits PDI open actions and blocks a member from another person', function () {
    $ownedPerson = Person::factory()->create();
    $otherPerson = Person::factory()->create();
    $plan = DevelopmentPlan::factory()->create(['person_id' => $ownedPerson->id, 'status' => 'active']);
    DevelopmentPlanItem::factory()->count(2)->create(['development_plan_id' => $plan->id, 'status' => 'todo']);
    $member = User::factory()->member()->create(['person_id' => $ownedPerson->id]);

    FourTechLeadServer::actingAs($member, 'sanctum')
        ->tool(PdiGetOpenActions::class, ['person_id' => $ownedPerson->id, 'limit' => 1])
        ->assertOk()
        ->assertStructuredContent(fn ($json) => $json->has('data.plans.0.actions', 1));

    FourTechLeadServer::actingAs($member, 'sanctum')
        ->tool(PdiGetOpenActions::class, ['person_id' => $otherPerson->id])
        ->assertHasErrors(['This action is unauthorized.']);
});

it('searches authorized people by partial name through MCP', function () {
    $user = User::factory()->create();
    $person = Person::factory()->create(['name' => 'Lucas Farias']);
    Person::factory()->create(['name' => 'Mariana Souza']);

    FourTechLeadServer::actingAs($user, 'sanctum')
        ->tool(PeopleSearch::class, ['query' => 'lucas'])
        ->assertOk()
        ->assertStructuredContent(fn ($json) => $json
            ->where('data.query', 'lucas')
            ->has('data.people', 1)
            ->where('data.people.0.id', $person->id)
            ->where('data.people.0.name', 'Lucas Farias')
            ->etc());
});

it('limits a member MCP person search to their linked person', function () {
    $ownedPerson = Person::factory()->create(['name' => 'Lucas Farias']);
    Person::factory()->create(['name' => 'Lucas Martins']);
    $member = User::factory()->member()->create(['person_id' => $ownedPerson->id]);

    FourTechLeadServer::actingAs($member, 'sanctum')
        ->tool(PeopleSearch::class, ['query' => 'Lucas'])
        ->assertOk()
        ->assertStructuredContent(fn ($json) => $json
            ->has('data.people', 1)
            ->where('data.people.0.id', $ownedPerson->id)
            ->where('data.people.0.name', 'Lucas Farias')
            ->etc());
});

it('does not allow a member to read another person through MCP', function () {
    $ownedPerson = Person::factory()->create();
    $otherPerson = Person::factory()->create();
    $member = User::factory()->member()->create([
        'person_id' => $ownedPerson->id,
        'role' => UserRole::Member,
    ]);

    FourTechLeadServer::actingAs($member, 'sanctum')
        ->tool(OneOnOneGetHistory::class, ['person_id' => $otherPerson->id])
        ->assertHasErrors(['This action is unauthorized.']);
});

it('rejects an unauthenticated MCP tool call', function () {
    FourTechLeadServer::tool(PeopleGetContext::class, ['person_id' => 1])
        ->assertHasErrors(['Authentication is required']);
});

it('advertises OAuth reauthentication for unauthenticated MCP tool calls', function () {
    expect(McpAuthenticationResponse::required()->getMeta())
        ->toHaveKey('mcp/www_authenticate.0')
        ->and(McpAuthenticationResponse::required()->getMeta()['mcp/www_authenticate'][0])
        ->toContain('resource_metadata="'.url('/.well-known/oauth-protected-resource/mcp/4techlead').'"')
        ->toContain('scope="mcp:use"');
});

it('protects the remote MCP endpoint with Sanctum', function () {
    $this->postJson('/mcp/4techlead', [])
        ->assertUnauthorized()
        ->assertHeader('WWW-Authenticate', 'Bearer realm="mcp", resource_metadata="'.url('/.well-known/oauth-protected-resource/mcp/4techlead').'", scope="mcp:use"');
});

it('publishes OAuth metadata for the remote MCP endpoint', function () {
    $this->getJson('/.well-known/oauth-protected-resource/mcp/4techlead')
        ->assertOk()
        ->assertJsonPath('resource', url('/mcp/4techlead'))
        ->assertJsonPath('scopes_supported.0', 'mcp:use');

    $this->getJson('/.well-known/oauth-authorization-server/mcp/4techlead')
        ->assertOk()
        ->assertJsonPath('authorization_endpoint', url('/oauth/authorize'))
        ->assertJsonPath('token_endpoint', url('/oauth/token'))
        ->assertJsonPath('scopes_supported.0', 'mcp:use');
});

it('registers the MCP authorization view and web login entrypoint', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('Entrar no 4TechLead');

    expect(view()->exists('mcp.authorize'))->toBeTrue();
});

it('completes the Passport MCP authorization flow with the real consent screen', function () {
    $key = openssl_pkey_new([
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ]);
    openssl_pkey_export($key, $privateKey);
    $publicKey = openssl_pkey_get_details($key)['key'];
    config([
        'passport.private_key' => $privateKey,
        'passport.public_key' => $publicKey,
    ]);

    $user = User::factory()->create();
    $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient(
        'Codex',
        ['https://client.example/callback'],
        confidential: false,
    );
    $verifier = 'verifier-that-is-long-enough-for-pkce-1234567890';
    $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    $query = http_build_query([
        'client_id' => $client->getKey(),
        'redirect_uri' => 'https://client.example/callback',
        'response_type' => 'code',
        'scope' => 'mcp:use',
        'state' => 'test-state',
        'code_challenge' => $challenge,
        'code_challenge_method' => 'S256',
    ]);

    $authorization = $this->actingAs($user, 'web')
        ->get('/oauth/authorize?'.$query)
        ->assertOk();

    $authorization->assertSee('Autorizar acesso ao Codex');
    $authToken = $authorization->viewData('authToken');

    $approval = $this->post('/oauth/mcp/authorize', [
        '_token' => csrf_token(),
        'auth_token' => $authToken,
    ])->assertRedirect();

    parse_str((string) parse_url((string) $approval->headers->get('Location'), PHP_URL_QUERY), $redirectQuery);

    expect($redirectQuery)
        ->toHaveKey('code')
        ->toHaveKey('state', 'test-state');

    $this->postJson('/oauth/token', [
        'grant_type' => 'authorization_code',
        'client_id' => $client->getKey(),
        'redirect_uri' => 'https://client.example/callback',
        'code' => $redirectQuery['code'],
        'code_verifier' => $verifier,
    ])->assertOk()
        ->assertJsonStructure(['token_type', 'expires_in', 'access_token']);
});

it('keeps concurrent MCP authorizations valid for the same signed-in user', function () {
    $key = openssl_pkey_new([
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ]);
    openssl_pkey_export($key, $privateKey);
    $publicKey = openssl_pkey_get_details($key)['key'];
    config([
        'passport.private_key' => $privateKey,
        'passport.public_key' => $publicKey,
    ]);

    $user = User::factory()->create();
    $client = app(ClientRepository::class)->createAuthorizationCodeGrantClient(
        'Codex',
        ['https://client.example/callback'],
        confidential: false,
    );
    $query = http_build_query([
        'client_id' => $client->getKey(),
        'redirect_uri' => 'https://client.example/callback',
        'response_type' => 'code',
        'scope' => 'mcp:use',
        'state' => 'first-request',
        'code_challenge' => 'iyjvC_1uGXib37nE6TMkVFLrjFwsDE-NKfQx2mJHPPY',
        'code_challenge_method' => 'S256',
    ]);

    $firstAuthorization = $this->actingAs($user, 'web')
        ->get('/oauth/authorize?'.$query)
        ->assertOk();
    $firstToken = $firstAuthorization->viewData('authToken');

    $this->get('/oauth/authorize?'.$query)
        ->assertOk();

    $this->post('/oauth/mcp/authorize', [
        '_token' => csrf_token(),
        'auth_token' => $firstToken,
    ])->assertRedirect()
        ->assertRedirectContains('state=first-request');
});

it('completes the MCP initialize handshake for an authenticated client', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/mcp/4techlead', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2025-11-25',
                'capabilities' => [],
                'clientInfo' => [
                    'name' => '4techlead-test-client',
                    'version' => '1.0.0',
                ],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('result.serverInfo.name', 'Four Tech Lead Server')
        ->assertJsonPath('result.instructions', 'Access authorized 4TechLead leadership data through read-only tools, plus proposal/commit tools for approved drafts and notes. Writes require explicit confirmation and a matching approval_id.');
});

it('propagates the authenticated HTTP guard to MCP tool calls', function () {
    $user = User::factory()->create();
    $person = Person::factory()->create(['name' => 'Lucas Farias']);

    $this->actingAs($user, 'sanctum')
        ->postJson('/mcp/4techlead', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => 'people.search',
                'arguments' => ['query' => 'Lucas'],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('result.structuredContent.data.people.0.id', $person->id)
        ->assertJsonPath('result.structuredContent.data.people.0.name', 'Lucas Farias');
});
