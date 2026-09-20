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
])]
class DeliveryCaseActivity extends Model
{
    use BelongsToTenant;

    protected $table = 'delivery_case_activities';

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

    public function author(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'author_person_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'reviewer_person_id');
    }

    protected function casts(): array
    {
        return [
            'numeric_value' => 'decimal:2',
            'occurred_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
