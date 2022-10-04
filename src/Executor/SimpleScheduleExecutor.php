<?php

declare(strict_types=1);

namespace Brzuchal\Scheduler\Executor;

use Brzuchal\RecurrenceRule\RuleIterator;
use Brzuchal\Scheduler\ScheduleExecutor;
use Brzuchal\Scheduler\ScheduleState;
use Brzuchal\Scheduler\Store\ScheduleStore;
use Closure;
use DateTimeImmutable;
use UnexpectedValueException;

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
        $this->store->updateSchedule($identifier, ScheduleState::InProgress);
        ($this->dispatcher)($schedule->message());
        $rule = $schedule->rule();
        if ($rule === null) {
            $this->store->updateSchedule($identifier, ScheduleState::Completed);

            return;
        }

        $start = $schedule->startDateTime();
        if ($start === null) {
            throw new UnexpectedValueException(
                'Scheduled message include recurrence rule but no start time',
            );
        }

        $now = new DateTimeImmutable('now');
        foreach (new RuleIterator($start, $rule) as $occurrence) {
            if ($occurrence < $now) {
                continue;
            }

            $this->store->updateSchedule($identifier, ScheduleState::Pending, $occurrence);

            return;
        }

        $this->store->updateSchedule($identifier, ScheduleState::Completed);
    }
}
