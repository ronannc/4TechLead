<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryCaseExternalLinkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'link_type' => $this->link_type?->value ?? $this->link_type,
            'external_id' => $this->external_id,
            'source_ref' => $this->source_ref,
            'metadata' => $this->metadata,
        ];
    }
}
