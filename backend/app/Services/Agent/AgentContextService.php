<?php

namespace App\Services\Agent;

use App\Models\DeliveryCase;
use App\Models\DevelopmentPlan;
use App\Models\OneOnOneSession;
use App\Models\Person;
use App\Models\User;
use App\Services\DeliveryKpiSummaryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Gate;

final class AgentContextService
{
    public function __construct(
        private readonly DeliveryKpiSummaryService $kpiSummaryService,
    ) {}

    /**
     * @return array{query: string, people: array<int, array<string, mixed>>}
     */
    public function searchPeople(User $user, string $query, int $limit = 10): array
    {
        $query = trim($query);
        $limit = min(max($limit, 1), 50);

        $people = Person::query()
            ->with('team')
            ->when($user->tenant_id !== null, fn (Builder $builder): Builder => $builder->where('tenant_id', $user->tenant_id))
            ->when(
                $user->isMember(),
                fn (Builder $builder): Builder => $builder->whereKey($user->person_id),
            )
            ->where('name', 'like', "%{$query}%")
            ->orderBy('name')
            ->limit($limit)
            ->get();

        return [
            'query' => $query,
            'people' => $people->map(fn (Person $person): array => $this->personData($person))->values()->all(),
        ];
    }

    public function getPersonContext(User $user, int $personId): array
    {
        $person = $this->findAuthorizedPerson($user, $personId);

        $sessions = $this->sessionsFor($user, $person)
            ->with('template')
            ->latest('held_at')
            ->latest('created_at')
            ->limit(5)
            ->get();

        $developmentPlans = DevelopmentPlan::query()
            ->where('person_id', $person->id)
            ->with('items')
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->latest('updated_at')
            ->limit(5)
            ->get();

        return [
            'person' => $this->personData($person),
            'recent_one_on_ones' => $sessions->map(fn (OneOnOneSession $session): array => $this->sessionData($session))->values()->all(),
            'active_development_plans' => $developmentPlans->map(fn (DevelopmentPlan $plan): array => $this->developmentPlanData($plan))->values()->all(),
        ];
    }

    /**
     * @return array{person_id: int, sessions: array<int, array<string, mixed>>}
     */
    public function getOneOnOneHistory(User $user, int $personId, int $limit = 10): array
    {
        $person = $this->findAuthorizedPerson($user, $personId);
        $limit = min(max($limit, 1), 50);

        $sessions = $this->sessionsFor($user, $person)
            ->with('template')
            ->latest('held_at')
            ->latest('created_at')
            ->limit($limit)
            ->get();

        return [
            'person_id' => $person->id,
            'sessions' => $sessions->map(fn (OneOnOneSession $session): array => $this->sessionData($session))->values()->all(),
        ];
    }

    /**
     * @return array{person_id: int, plans: array<int, array<string, mixed>>}
     */
    public function getOpenPdiActions(User $user, int $personId, int $limit = 25): array
    {
        $person = $this->findAuthorizedPerson($user, $personId);
        $limit = min(max($limit, 1), 50);

        $plans = DevelopmentPlan::query()
            ->where('person_id', $person->id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->with(['items' => fn (HasMany $query): HasMany => $query
                ->whereNotIn('status', ['done', 'completed', 'cancelled'])
                ->orderByRaw('due_date is null')
                ->orderBy('due_date')
                ->orderBy('id')
                ->limit($limit)])
            ->latest('updated_at')
            ->get();

        $remaining = $limit;
        $data = [];

        foreach ($plans as $plan) {
            if ($remaining === 0) {
                break;
            }

            $items = $plan->items->take($remaining);
            $remaining -= $items->count();

            if ($items->isEmpty()) {
                continue;
            }

            $data[] = [
                'id' => $plan->id,
                'title' => $plan->title,
                'status' => $plan->status,
                'progress' => $plan->progress,
                'end_date' => $plan->end_date?->toDateString(),
                'actions' => $items->map(fn ($item): array => [
                    'id' => $item->id,
                    'title' => $item->title,
                    'description' => $item->description,
                    'competency' => $item->competency,
                    'evidence' => $item->evidence,
                    'status' => $item->status,
                    'due_date' => $item->due_date?->toDateString(),
                    'progress' => $item->progress,
                ])->values()->all(),
            ];
        }

        return [
            'person_id' => $person->id,
            'plans' => $data,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getKpiSummary(User $user, array $filters = []): array
    {
        Gate::forUser($user)->authorize('viewAny', DeliveryCase::class);

        $tenantId = $user->tenant_id;

        abort_if($tenantId === null, 403);

        if (isset($filters['person_id'])) {
            $this->findAuthorizedPerson($user, (int) $filters['person_id']);
        }

        return $this->kpiSummaryService->summarize($tenantId, $filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getKpiEvidence(User $user, array $filters = [], int $limit = 50): array
    {
        Gate::forUser($user)->authorize('viewAny', DeliveryCase::class);

        $tenantId = $user->tenant_id;

        abort_if($tenantId === null, 403);

        if (isset($filters['person_id'])) {
            $this->findAuthorizedPerson($user, (int) $filters['person_id']);
        }

        return $this->kpiSummaryService->evidence($tenantId, $filters, $limit);
    }

    private function findAuthorizedPerson(User $user, int $personId): Person
    {
        $query = Person::query()->with('team');

        if ($user->tenant_id !== null) {
            $query->where('tenant_id', $user->tenant_id);
        }

        $person = $query->findOrFail($personId);

        Gate::forUser($user)->authorize('view', $person);

        return $person;
    }

    /**
     * @return Builder<OneOnOneSession>
     */
    private function sessionsFor(User $user, Person $person): Builder
    {
        Gate::forUser($user)->authorize('viewAny', OneOnOneSession::class);

        return OneOnOneSession::query()->where('person_id', $person->id);
    }

    /**
     * @return array<string, mixed>
     */
    private function personData(Person $person): array
    {
        return [
            'id' => $person->id,
            'name' => $person->name,
            'team' => $person->team?->name,
            'position' => $person->position,
            'seniority' => $person->seniority?->value,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function sessionData(OneOnOneSession $session): array
    {
        return [
            'id' => $session->id,
            'person_id' => $session->person_id,
            'title' => $session->title,
            'status' => $session->status,
            'sentiment' => $session->sentiment,
            'scheduled_for' => $session->scheduled_for?->toDateString(),
            'held_at' => $session->held_at?->toDateString(),
            'questions' => $session->questions,
            'answers' => $session->answers,
            'notes' => $session->notes,
            'action_items' => $session->action_items,
            'document_snapshot' => $session->document_snapshot,
            'template_title' => $session->template?->title,
            'created_at' => $session->created_at?->toIso8601String(),
            'updated_at' => $session->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function developmentPlanData(DevelopmentPlan $plan): array
    {
        return [
            'id' => $plan->id,
            'title' => $plan->title,
            'summary' => $plan->summary,
            'status' => $plan->status,
            'start_date' => $plan->start_date?->toDateString(),
            'end_date' => $plan->end_date?->toDateString(),
            'progress' => $plan->progress,
            'items' => $plan->items->map(fn ($item): array => [
                'id' => $item->id,
                'title' => $item->title,
                'description' => $item->description,
                'competency' => $item->competency,
                'evidence' => $item->evidence,
                'status' => $item->status,
                'due_date' => $item->due_date?->toDateString(),
                'progress' => $item->progress,
            ])->values()->all(),
        ];
    }
}
