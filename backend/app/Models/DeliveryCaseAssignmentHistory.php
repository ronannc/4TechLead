<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'tenant_id',
    'delivery_case_id',
    'source_webhook_id',
    'assignee_person_id',
    'assigned_at',
    'unassigned_at',
    'metadata',
])]
class DeliveryCaseAssignmentHistory extends Model
{
    use BelongsToTenant;

    protected $table = 'delivery_case_assignment_history';

    public function deliveryCase(): BelongsTo
    {
        return $this->belongsTo(DeliveryCase::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'assignee_person_id');
    }

    public function sourceWebhook(): BelongsTo
    {
        return $this->belongsTo(IntegrationWebhookEvent::class, 'source_webhook_id');
    }

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'unassigned_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
