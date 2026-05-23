<?php

namespace Domain\Driver\Actions;

use Domain\Driver\Models\Entities\Driver;
use Domain\Shared\Actions\Action;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetDriversAction extends Action
{
    public function __construct(private readonly int $perPage = 15) {}

    public function handle(): LengthAwarePaginator
    {
        return Driver::with(['user', 'vehicle', 'location'])
            ->orderBy('id')
            ->paginate($this->perPage);
    }
}
