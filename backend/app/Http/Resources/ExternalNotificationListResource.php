<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExternalNotificationListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'integration_system_id' => $this->integration_system_id,
            'integration_system' => $this->whenLoaded('integrationSystem', fn () => [
                'id' => $this->integrationSystem->id,
                'name' => $this->integrationSystem->name,
                'provider' => $this->integrationSystem->provider,
            ]),
            'event_id' => $this->event_id,
            'title' => $this->title,
            'message' => $this->message,
            'content' => $this->message,
            'type' => $this->type,
            'severity' => $this->severity,
            'source_ref' => $this->source_ref,
            'received_at' => $this->received_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
