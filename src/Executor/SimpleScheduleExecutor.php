<?php

declare(strict_types=1);

namespace Brzuchal\Scheduler\Executor;

use Brzuchal\Scheduler\ScheduleExecutor;
use Brzuchal\Scheduler\Store\ScheduleStore;
use Closure;

final class SimpleScheduleExecutor implements ScheduleExecutor
{
    public function __construct(
        protected ScheduleStore $store,
        protected Closure $dispatcher,
    ) {
    }

    public function execute(string $identifier): void
    {
        $schedule = $this->store->findSchedule($identifier);
        ($this->dispatcher)($schedule->message());
    }
}
