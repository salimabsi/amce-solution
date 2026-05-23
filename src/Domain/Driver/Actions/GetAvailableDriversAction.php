<?php

namespace Domain\Driver\Actions;

use Domain\Driver\Models\Entities\Driver;
use Domain\Shared\Actions\Action;
use Illuminate\Database\Eloquent\Collection;

class GetAvailableDriversAction extends Action
{
    public function handle(): Collection
    {
        return Driver::where('is_available', true)
            ->with(['vehicle', 'location'])
            ->get();
    }
}
