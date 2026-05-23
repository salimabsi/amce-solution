<?php

namespace Domain\Driver\Actions;

use Domain\Driver\Contracts\DriverLocationStoreContract;
use Domain\Driver\Models\Entities\Driver;
use Domain\Shared\Actions\Action;
use Illuminate\Database\Eloquent\Collection;

class GetAvailableDriversNearbyAction extends Action
{
    public function __construct(
        private readonly DriverLocationStoreContract $store,
        private readonly float $lat,
        private readonly float $lng,
        private readonly float $radiusKm,
    ) {}

    public function handle(): Collection
    {
        $nearbyIds = $this->store->findNearbyIds($this->lat, $this->lng, $this->radiusKm);

        if (empty($nearbyIds)) {
            return new Collection;
        }

        return Driver::query()
            ->whereIn('id', $nearbyIds)
            ->where('is_available', true)
            ->with(['vehicle', 'location'])
            ->get();
    }
}
