<?php

namespace App\Services;

use App\Contracts\Services\IndexServiceContract;
use App\DTOs\ListParams;
use App\Models\IntegrationWebhookEvent;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class IntegrationWebhookEventIndexService implements IndexServiceContract
{
    public function index(ListParams $params): LengthAwarePaginator
    {
        return IntegrationWebhookEvent::query()
            ->select([
                'id',
                'integration_system_id',
                'person_id',
                'event_id',
                'event_type',
                'external_actor_code',
                'status',
                'projection_status',
                'processing_reason',
                'enrichment_status',
                'failure_reason',
                'payload_size_bytes',
                'received_at',
                'created_at',
                'updated_at',
            ])
            ->with(['integrationSystem', 'person'])
            ->withCount('deliveryMetrics')
            ->filter($params->filters)
            ->operationalFilters($params->filters)
            ->search($params->search)
            ->operationalOrder($params->order)
            ->paginate(perPage: $params->perPage, page: $params->page);
    }
}
