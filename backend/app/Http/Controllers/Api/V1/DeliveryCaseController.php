<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\DeliveryCaseResource;
use App\Models\DeliveryCase;
use Illuminate\Http\JsonResponse;

final class DeliveryCaseController extends Controller
{
    public function show(DeliveryCase $deliveryCase): JsonResponse
    {
        $this->authorize('view', $deliveryCase);

        $deliveryCase->load([
            'currentAssignee:id,name',
            'statusHistory' => fn ($query) => $query->select([
                'id',
                'delivery_case_id',
                'assignee_person_id',
                'status_changed_by_person_id',
                'from_stage',
                'to_stage',
                'occurred_at',
                'metadata',
            ])->with([
                'assignee:id,name',
                'statusChangedBy:id,name',
            ]),
            'assignmentHistory' => fn ($query) => $query->select([
                'id',
                'delivery_case_id',
                'assignee_person_id',
                'assigned_at',
                'unassigned_at',
                'metadata',
            ])->with('assignee:id,name'),
            'activities' => fn ($query) => $query->select([
                'id',
                'delivery_case_id',
                'assignee_person_id',
                'author_person_id',
                'reviewer_person_id',
                'activity_type',
                'repository',
                'branch',
                'pull_request_number',
                'commit_sha',
                'workflow_run_id',
                'numeric_value',
                'occurred_at',
                'metadata',
            ])->with([
                'assignee:id,name',
                'author:id,name',
                'reviewer:id,name',
            ]),
            'externalLinks:id,delivery_case_id,link_type,external_id,source_ref,metadata',
            'milestones:id,delivery_case_id,milestone_type,source_provider,source_ref,occurred_at,metadata',
        ]);

        return (new DeliveryCaseResource($deliveryCase))->response();
    }
}
