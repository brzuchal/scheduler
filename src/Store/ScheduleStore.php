<?php

declare(strict_types=1);

namespace Brzuchal\Scheduler\Store;

use Brzuchal\RecurrenceRule\Rule;
use Brzuchal\Scheduler\ScheduleState;
use DateTimeImmutable;

interface ScheduleStore
{
    public function findSchedule(string $identifier): ScheduleStoreEntry;

    public function insertSchedule(
        string $identifier,
        DateTimeImmutable $triggerDateTime,
        object $message,
        Rule|null $rule = null,
        DateTimeImmutable|null $startDateTime = null
    ): void;

    public function updateSchedule(
        string $identifier,
        DateTimeImmutable $triggerDateTime,
        ScheduleState $state,
        Rule|null $rule = null,
        DateTimeImmutable|null $startDateTime = null
    ): void;

    public function deleteSchedule(string $identifier): void;

    /**
     * @return iterable<string> List of identifiers
     */
    public function findPendingSchedules(DateTimeImmutable $date): iterable;
}
