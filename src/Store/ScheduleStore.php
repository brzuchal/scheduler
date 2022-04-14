<?php

declare(strict_types=1);

namespace Brzuchal\Scheduler\Store;

use Brzuchal\Scheduler\ScheduleState;
use DateInterval;
use DateTimeImmutable;

interface ScheduleStore
{
    public function findSchedule(string $identifier): ScheduleStoreEntry;

    public function insertSchedule(
        string $identifier,
        DateTimeImmutable $triggerDateTime,
        object $message,
        DateInterval|null $interval = null,
    ): void;

    public function updateSchedule(
        string $identifier,
        DateTimeImmutable $triggerDateTime,
        ScheduleState $state,
    ): void;

    public function deleteSchedule(string $identifier): void;

    /**
     * @return iterable<string> List of identifiers
     */
    public function findPendingSchedules(DateTimeImmutable $date): iterable;
}
