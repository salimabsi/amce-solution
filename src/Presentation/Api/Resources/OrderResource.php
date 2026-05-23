<?php

namespace Presentation\Api\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'type' => $this->type,
            'priority' => $this->priority,
            'weight_kg' => $this->weight_kg,
            'pickup' => [
                'lat' => $this->pickup_lat,
                'lng' => $this->pickup_lng,
            ],
            'dropoff' => [
                'lat' => $this->dropoff_lat,
                'lng' => $this->dropoff_lng,
            ],
            'driver_id' => $this->driver_id,
            'assigned_at' => $this->assigned_at,
            'created_at' => $this->created_at,
        ];
    }
}
