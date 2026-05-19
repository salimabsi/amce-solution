<?php

namespace Domain\Shared\Actions;

abstract class Action
{
    abstract public function handle(): mixed;
}
