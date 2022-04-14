<?php

declare(strict_types=1);

namespace Brzuchal\Scheduler;

interface ScheduleExecutor
{
    public function execute(string $identifier): void;
}
