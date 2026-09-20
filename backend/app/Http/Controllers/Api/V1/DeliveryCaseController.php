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
            'currentAssignee',
            'statusHistory.assignee',
            'statusHistory.statusChangedBy',
            'assignmentHistory.assignee',
            'activities.assignee',
            'activities.author',
            'activities.reviewer',
            'externalLinks',
            'milestones',
        ]);

        return (new DeliveryCaseResource($deliveryCase))->response();
    }
}
