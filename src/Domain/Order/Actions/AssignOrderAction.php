<?php

namespace Domain\Order\Actions;

use Domain\Driver\Contracts\DriverServiceContract;
use Domain\Order\Contracts\DriverFilterContract;
use Domain\Order\Contracts\DriverScorerContract;
use Domain\Order\Exceptions\NoAvailableDriverException;
use Domain\Order\Exceptions\OrderNotFoundException;
use Domain\Order\Models\Entities\Order;
use Domain\Shared\Actions\Action;
use Illuminate\Support\Facades\DB;

class AssignOrderAction extends Action
{
    /** @param DriverFilterContract[] $filters
     *  @param DriverScorerContract[] $scorers */
    public function __construct(
        private readonly int $orderId,
        private readonly DriverServiceContract $driverService,
        private readonly array $filters,
        private readonly array $scorers,
    ) {}

    public function handle(): Order
    {
        $order = Order::find($this->orderId) ?? throw new OrderNotFoundException($this->orderId);

        $drivers = $this->driverService->getAvailableDrivers();

        foreach ($this->filters as $filter) {
            $drivers = $filter->filter($drivers, $order);
        }

        if ($drivers->isEmpty()) {
            throw new NoAvailableDriverException($this->orderId);
        }

        $bestDriver = $drivers->sortByDesc(
            fn ($driver) => collect($this->scorers)->sum(fn ($scorer) => $scorer->score($driver, $order))
        )->first();

        return DB::transaction(function () use ($bestDriver) {
            $order = (new MarkOrderAsAssignedAction($this->orderId, $bestDriver->id))->handle();
            $this->driverService->markUnavailable($bestDriver->id);

            return $order;
        });
    }
}
