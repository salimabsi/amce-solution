<?php

namespace Presentation\Api\Requests;

use Domain\Order\DataTransferObjects\CreateOrderData;
use Domain\Order\Enums\OrderPriority;
use Domain\Order\Enums\OrderType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::enum(OrderType::class)],
            'priority' => ['sometimes', 'string', Rule::enum(OrderPriority::class)],
            'weight_kg' => ['required', 'numeric', 'min:0.01', 'max:50000'],
            'pickup_lat' => ['required', 'numeric', 'between:-90,90'],
            'pickup_lng' => ['required', 'numeric', 'between:-180,180'],
            'dropoff_lat' => ['required', 'numeric', 'between:-90,90'],
            'dropoff_lng' => ['required', 'numeric', 'between:-180,180'],
        ];
    }

    public function toData(): CreateOrderData
    {
        $v = $this->validated();

        return new CreateOrderData(
            type: OrderType::from($v['type']),
            priority: OrderPriority::from($v['priority'] ?? OrderPriority::Normal->value),
            weightKg: (float) $v['weight_kg'],
            pickupLat: (float) $v['pickup_lat'],
            pickupLng: (float) $v['pickup_lng'],
            dropoffLat: (float) $v['dropoff_lat'],
            dropoffLng: (float) $v['dropoff_lng'],
        );
    }
}
