<?php

declare(strict_types=1);

namespace Brzuchal\Scheduler\Store;

interface SetupableScheduleStore
{
    public function setup(): void;
}
