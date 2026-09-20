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
    'status_changed_by_person_id',
    'assignee_person_id',
    'from_stage',
    'to_stage',
    'occurred_at',
    'metadata',
])]
class DeliveryCaseStatusHistory extends Model
{
    use BelongsToTenant;

    protected $table = 'delivery_case_status_history';

    public function deliveryCase(): BelongsTo
    {
        return $this->belongsTo(DeliveryCase::class);
    }

    public function sourceWebhook(): BelongsTo
    {
        return $this->belongsTo(IntegrationWebhookEvent::class, 'source_webhook_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'assignee_person_id');
    }

    public function statusChangedBy(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'status_changed_by_person_id');
    }

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
