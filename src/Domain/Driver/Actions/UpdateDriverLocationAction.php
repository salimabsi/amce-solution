<?php

namespace Domain\Driver\Actions;

use Domain\Driver\DataTransferObjects\DriverLocationData;
use Domain\Driver\Models\Entities\Driver;
use Domain\Shared\Actions\Action;

class UpdateDriverLocationAction extends Action
{
    public function __construct(
        private readonly int $driverId,
        private readonly DriverLocationData $data,
    ) {}

    public function handle(): void
    {
        Driver::findOrFail($this->driverId)
            ->location()
            ->updateOrCreate(
                ['driver_id' => $this->driverId],
                ['lat' => $this->data->lat, 'lng' => $this->data->lng],
            );
    }
}
