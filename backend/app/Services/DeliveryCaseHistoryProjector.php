<?php

namespace App\Services;

use App\Enums\DeliveryParticipantRole;
use App\Enums\DeliveryStage;
use App\Models\DeliveryCase;
use App\Models\DeliveryCaseActivity;
use App\Models\DeliveryCaseAssignmentHistory;
use App\Models\DeliveryCaseParticipant;
use App\Models\DeliveryCaseStatusHistory;
use App\Models\IntegrationWebhookEvent;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

final class DeliveryCaseHistoryProjector
{
    /**
     * @param  Collection<int, IntegrationWebhookEvent>  $events
     */
    public function project(DeliveryCase $deliveryCase, Collection $events): void
    {
        $participants = $this->implementerParticipants($deliveryCase);
        $this->projectAssignments($deliveryCase, $participants);
        $assignmentHistory = $this->assignmentHistory($deliveryCase);
        $this->projectStatusHistory($deliveryCase, $events, $participants);
        $this->projectGithubActivities($deliveryCase, $events, $assignmentHistory);
        $this->updateCurrentAssignee($deliveryCase);
    }

    /**
     * @return Collection<int, DeliveryCaseParticipant>
     */
    private function implementerParticipants(DeliveryCase $deliveryCase): Collection
    {
        return DeliveryCaseParticipant::query()
            ->withoutGlobalScopes()
            ->where('delivery_case_id', $deliveryCase->id)
            ->where('role', DeliveryParticipantRole::Implementer->value)
            ->orderBy('valid_from')
            ->get();
    }

    /**
     * @param  Collection<int, DeliveryCaseParticipant>  $participants
     */
    private function projectAssignments(DeliveryCase $deliveryCase, Collection $participants): void
    {
        $sourceRefs = $participants->pluck('source_ref')->filter()->values();
        $sourceWebhookIds = $sourceRefs->isEmpty()
            ? collect()
            : IntegrationWebhookEvent::query()
                ->withoutGlobalScopes()
                ->whereIn('event_id', $sourceRefs)
                ->pluck('id', 'event_id');

        foreach ($participants as $participant) {
            DeliveryCaseAssignmentHistory::query()->withoutGlobalScopes()->updateOrCreate(
                [
                    'delivery_case_id' => $deliveryCase->id,
                    'assignee_person_id' => $participant->person_id,
                    'assigned_at' => $participant->valid_from,
                ],
                [
                    'tenant_id' => $deliveryCase->tenant_id,
                    'source_webhook_id' => $sourceWebhookIds->get($participant->source_ref),
                    'unassigned_at' => $participant->valid_to,
                    'metadata' => $participant->metadata,
                ],
            );
        }
    }

    /**
     * @param  Collection<int, DeliveryCaseParticipant>  $participants
     */
    private function projectStatusHistory(
        DeliveryCase $deliveryCase,
        Collection $events,
        Collection $participants,
    ): void
    {
        $previousStage = null;

        foreach ($events as $event) {
            if (data_get($event->normalized_payload, 'source') !== 'clickup') {
                continue;
            }

            $stage = $this->stageFor($event);

            if ($stage === null || $stage === $previousStage) {
                continue;
            }

            $occurredAt = $this->occurredAt($event);
            $assigneeId = $this->assigneeAt($participants, $occurredAt);

            DeliveryCaseStatusHistory::query()->withoutGlobalScopes()->updateOrCreate(
                [
                    'delivery_case_id' => $deliveryCase->id,
                    'source_webhook_id' => $event->id,
                    'to_stage' => $stage->value,
                ],
                [
                    'tenant_id' => $deliveryCase->tenant_id,
                    'status_changed_by_person_id' => $event->person_id,
                    'assignee_person_id' => $assigneeId,
                    'from_stage' => $previousStage?->value,
                    'occurred_at' => $occurredAt,
                    'metadata' => [
                        'task_id' => data_get($event->normalized_payload, 'task_id'),
                        'status' => data_get($event->normalized_payload, 'history_after')
                            ?? data_get($event->normalized_payload, 'task_status'),
                    ],
                ],
            );

            $previousStage = $stage;
        }
    }

    /**
     * @param  Collection<int, DeliveryCaseAssignmentHistory>  $assignmentHistory
     */
    private function projectGithubActivities(
        DeliveryCase $deliveryCase,
        Collection $events,
        Collection $assignmentHistory,
    ): void
    {
        foreach ($events as $event) {
            if (data_get($event->normalized_payload, 'source') !== 'github') {
                continue;
            }

            $activityType = $this->activityType($event);

            if ($activityType === null) {
                continue;
            }

            $payload = $event->normalized_payload ?? [];
            $occurredAt = $this->occurredAt($event);
            $assigneeId = $this->assignmentAt($assignmentHistory, $occurredAt);

            DeliveryCaseActivity::query()->withoutGlobalScopes()->updateOrCreate(
                [
                    'source_webhook_id' => $event->id,
                    'activity_type' => $activityType,
                ],
                [
                    'tenant_id' => $deliveryCase->tenant_id,
                    'delivery_case_id' => $deliveryCase->id,
                    'assignee_person_id' => $assigneeId,
                    'author_person_id' => $this->authorPersonId($event, $activityType),
                    'reviewer_person_id' => $activityType === 'review_approved'
                        || $activityType === 'changes_requested'
                        || $activityType === 'review_commented'
                        ? $event->person_id
                        : null,
                    'repository' => $this->stringValue($payload, 'repository_id'),
                    'branch' => $this->stringValue($payload, 'head_branch')
                        ?? $this->stringValue($payload, 'branch'),
                    'pull_request_number' => $this->stringValue($payload, 'pr_number'),
                    'commit_sha' => $this->stringValue($payload, 'head_sha'),
                    'workflow_run_id' => $this->stringValue($payload, 'workflow_run_id'),
                    'numeric_value' => $this->numericValue($payload, $activityType),
                    'occurred_at' => $occurredAt,
                    'metadata' => $payload,
                ],
            );
        }
    }

    /**
     * @return Collection<int, DeliveryCaseAssignmentHistory>
     */
    private function assignmentHistory(DeliveryCase $deliveryCase): Collection
    {
        return DeliveryCaseAssignmentHistory::query()
            ->withoutGlobalScopes()
            ->where('delivery_case_id', $deliveryCase->id)
            ->orderBy('assigned_at')
            ->get();
    }

    /**
     * @param  Collection<int, DeliveryCaseParticipant>  $participants
     */
    private function assigneeAt(Collection $participants, CarbonInterface $occurredAt): ?int
    {
        return $participants
            ->reverse()
            ->first(fn (DeliveryCaseParticipant $participant): bool => $participant->valid_from->lessThanOrEqualTo($occurredAt)
                && ($participant->valid_to === null || $participant->valid_to->greaterThan($occurredAt)))
            ?->person_id;
    }

    /**
     * @param  Collection<int, DeliveryCaseAssignmentHistory>  $assignmentHistory
     */
    private function assignmentAt(Collection $assignmentHistory, CarbonInterface $occurredAt): ?int
    {
        return $assignmentHistory
            ->reverse()
            ->first(fn (DeliveryCaseAssignmentHistory $assignment): bool => $assignment->assigned_at->lessThanOrEqualTo($occurredAt)
                && ($assignment->unassigned_at === null || $assignment->unassigned_at->greaterThan($occurredAt)))
            ?->assignee_person_id;
    }

    private function updateCurrentAssignee(DeliveryCase $deliveryCase): void
    {
        $assigneeId = DeliveryCaseAssignmentHistory::query()
            ->withoutGlobalScopes()
            ->where('delivery_case_id', $deliveryCase->id)
            ->whereNull('unassigned_at')
            ->latest('assigned_at')
            ->value('assignee_person_id');

        $deliveryCase->forceFill(['current_assignee_id' => $assigneeId])->save();
    }

    private function stageFor(IntegrationWebhookEvent $event): ?DeliveryStage
    {
        foreach (['history_after_stage', 'task_stage'] as $key) {
            $stage = data_get($event->normalized_payload, $key);

            if (is_string($stage) && DeliveryStage::tryFrom($stage) !== null) {
                return DeliveryStage::from($stage);
            }
        }

        return null;
    }

    private function activityType(IntegrationWebhookEvent $event): ?string
    {
        $type = (string) data_get($event->normalized_payload, 'event_type', $event->event_type);

        return match ($type) {
            'pull_request.opened' => 'pull_request_opened',
            'pull_request.closed' => data_get($event->normalized_payload, 'pr_merged') === true
                ? 'pull_request_merged'
                : 'pull_request_closed',
            'pull_request_review.submitted' => match (data_get($event->normalized_payload, 'review_state')) {
                'approved' => 'review_approved',
                'changes_requested' => 'changes_requested',
                'commented' => 'review_commented',
                default => null,
            },
            'workflow_run.completed' => data_get($event->normalized_payload, 'workflow_run_conclusion') === 'success'
                ? 'ci_succeeded'
                : 'ci_failed',
            'deployment_status.created' => in_array(
                data_get($event->normalized_payload, 'deployment_status_state'),
                ['success', 'failure', 'error'],
                true,
            ) ? 'deployment_completed' : null,
            default => null,
        };
    }

    private function authorPersonId(IntegrationWebhookEvent $event, string $activityType): ?int
    {
        return in_array($activityType, [
            'pull_request_opened',
            'pull_request_closed',
            'pull_request_merged',
        ], true) ? $event->person_id : null;
    }

    private function numericValue(array $payload, string $activityType): ?float
    {
        $value = match ($activityType) {
            'ci_succeeded', 'ci_failed' => 1,
            'pull_request_opened',
            'pull_request_closed',
            'pull_request_merged',
            'review_approved',
            'changes_requested',
            'review_commented' => 1,
            default => data_get($payload, 'changed_lines'),
        };

        return is_numeric($value) ? (float) $value : null;
    }

    private function stringValue(array $payload, string $key): ?string
    {
        $value = data_get($payload, $key);

        return is_scalar($value) && (string) $value !== '' ? (string) $value : null;
    }

    private function occurredAt(IntegrationWebhookEvent $event): CarbonInterface
    {
        $value = $event->occurred_at ?? data_get($event->normalized_payload, 'occurred_at');

        return $value instanceof CarbonInterface ? $value : ($event->received_at ?? now());
    }
}
