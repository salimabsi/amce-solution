<?php

namespace Presentation\Api\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->user?->name,
            'is_available' => $this->is_available,
            'vehicle' => $this->whenLoaded('vehicle', fn () => [
                'type' => $this->vehicle->type,
                'capacity_kg' => $this->vehicle->capacity_kg,
                'plate_number' => $this->vehicle->plate_number,
            ]),
            'location' => $this->whenLoaded('location', fn () => $this->location ? [
                'lat' => $this->location->lat,
                'lng' => $this->location->lng,
            ] : null),
        ];
    }
}
