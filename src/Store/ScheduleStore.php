<?php

declare(strict_types=1);

namespace Brzuchal\Scheduler\Store;

use Brzuchal\RecurrenceRule\Rule;
use Brzuchal\Scheduler\ScheduleState;
use DateTimeImmutable;

interface ScheduleStore
{
    /**
     * Retrieve existing schedule entry identified by given identifier.
     */
    public function findSchedule(string $identifier): ScheduleStoreEntry;

    /**
     * Creates a new schedule entry with {@see ScheduleState::Pending} state.
     */
    public function insertSchedule(
        string $identifier,
        DateTimeImmutable $triggerDateTime,
        object $message,
        Rule|null $rule = null,
        DateTimeImmutable|null $startDateTime = null
    ): void;

    /**
     * Updates an existing schedule entry state on various operations like cancelling or
     * updating new triggerDateTime when scheduling is controlled by recurrence rule.
     */
    public function updateSchedule(
        string $identifier,
        ScheduleState $state,
        DateTimeImmutable|null $triggerDateTime = null,
        Rule|null $rule = null,
    ): void;

    /**
     * Deletes an existing schedule entry if it exists. In other cases do nothing.
     */
    public function deleteSchedule(string $identifier): void;

    /**
     * Retrieves a list of schedule identifiers optionally narrowed down by date or limited
     * where state matches {@see ScheduleState::Pending}
     *
     * @return iterable<string> List of identifiers
     */
    public function findPendingSchedules(
        DateTimeImmutable|null $beforeDateTime = null,
        int|null $limit = null
    ): iterable;
}
