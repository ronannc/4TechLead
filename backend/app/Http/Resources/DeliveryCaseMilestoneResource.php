<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryCaseMilestoneResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'milestone_type' => $this->milestone_type?->value ?? $this->milestone_type,
            'source_provider' => $this->source_provider,
            'source_ref' => $this->source_ref,
            'occurred_at' => $this->occurred_at,
            'metadata' => $this->metadata,
        ];
    }
}
