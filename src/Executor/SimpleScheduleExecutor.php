<?php

declare(strict_types=1);

namespace Brzuchal\Scheduler\Executor;

use Brzuchal\RecurrenceRule\RuleIterator;
use Brzuchal\Scheduler\ScheduleExecutor;
use Brzuchal\Scheduler\ScheduleState;
use Brzuchal\Scheduler\Store\ScheduleStore;
use Closure;
use DateTimeImmutable;

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
        $rule = $schedule->rule();
        if ($rule === null) {
            // TODO: mark as done
            return;
        }

        $start = $schedule->startDateTime();
        if ($start === null) {
            throw new \UnexpectedValueException('Missing start time');
        }

        $now = new DateTimeImmutable('now');
        foreach (new RuleIterator($start, $rule) as $date) {
            if ($date < $now) {
                continue;
            }

            $this->store->updateSchedule(
                $identifier,
                $date,
                ScheduleState::Pending,
                $rule,
                $start,
            );

            break;
        }
    }
}
