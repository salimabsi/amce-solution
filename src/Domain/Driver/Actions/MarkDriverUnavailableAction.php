<?php

namespace Domain\Driver\Actions;

use Domain\Driver\Models\Entities\Driver;
use Domain\Shared\Actions\Action;

class MarkDriverUnavailableAction extends Action
{
    public function __construct(private readonly int $driverId) {}

    public function handle(): void
    {
        Driver::where('id', $this->driverId)->update(['is_available' => false]);
    }
}
