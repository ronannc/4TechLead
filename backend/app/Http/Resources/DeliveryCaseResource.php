<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryCaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'task_ref' => $this->task_ref,
            'clickup_task_id' => $this->clickup_task_id,
            'title' => $this->title,
            'current_stage' => $this->current_stage?->value ?? $this->current_stage,
            'current_assignee' => new CompactPersonResource($this->whenLoaded('currentAssignee')),
            'first_seen_at' => $this->first_seen_at,
            'last_activity_at' => $this->last_activity_at,
            'completed_at' => $this->completed_at,
            'canceled_at' => $this->canceled_at,
            'status_history' => $this->statusHistory->map(fn ($history): array => [
                'id' => $history->id,
                'from_stage' => $history->from_stage,
                'to_stage' => $history->to_stage,
                'assignee' => new CompactPersonResource($history->assignee),
                'status_changed_by' => new CompactPersonResource($history->statusChangedBy),
                'occurred_at' => $history->occurred_at,
                'metadata' => $history->metadata,
            ]),
            'assignment_history' => $this->assignmentHistory->map(fn ($assignment): array => [
                'id' => $assignment->id,
                'assignee' => new CompactPersonResource($assignment->assignee),
                'assigned_at' => $assignment->assigned_at,
                'unassigned_at' => $assignment->unassigned_at,
                'metadata' => $assignment->metadata,
            ]),
            'activities' => $this->activities->map(fn ($activity): array => [
                'id' => $activity->id,
                'activity_type' => $activity->activity_type,
                'assignee' => new CompactPersonResource($activity->assignee),
                'author' => new CompactPersonResource($activity->author),
                'reviewer' => new CompactPersonResource($activity->reviewer),
                'repository' => $activity->repository,
                'branch' => $activity->branch,
                'pull_request_number' => $activity->pull_request_number,
                'commit_sha' => $activity->commit_sha,
                'workflow_run_id' => $activity->workflow_run_id,
                'numeric_value' => $activity->numeric_value,
                'occurred_at' => $activity->occurred_at,
                'metadata' => $activity->metadata,
            ]),
            'external_links' => DeliveryCaseExternalLinkResource::collection($this->externalLinks),
            'milestones' => DeliveryCaseMilestoneResource::collection($this->milestones),
        ];
    }
}
