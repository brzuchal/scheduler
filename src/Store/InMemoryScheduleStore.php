<?php

declare(strict_types=1);

namespace Brzuchal\Scheduler\Store;

use Brzuchal\RecurrenceRule\Rule;
use Brzuchal\Scheduler\ScheduleState;
use DateTimeImmutable;

final class InMemoryScheduleStore implements ScheduleStore
{
    // phpcs:disable
    /** @psalm-var array<int, <string, SimpleScheduleStoreEntry>> */
    protected array $schedules;
    // phpcs:enable

    public function __construct()
    {
        $this->schedules = [
            ScheduleState::Pending->value => [],
            ScheduleState::InProgress->value => [],
            ScheduleState::Rejected->value => [],
        ];
    }

    public function findSchedule(string $identifier): ScheduleStoreEntry
    {
        return clone $this->schedules[ScheduleState::Pending->value][$identifier];
    }

    public function insertSchedule(
        string $identifier,
        DateTimeImmutable $triggerDateTime,
        object $message,
        Rule|null $rule = null,
        DateTimeImmutable|null $startDateTime = null,
    ): void {
        $this->schedules[ScheduleState::Pending->value][$identifier] = new SimpleScheduleStoreEntry(
            $triggerDateTime,
            $message,
            $rule,
            $startDateTime,
        );
    }

    public function updateSchedule(
        string $identifier,
        DateTimeImmutable $triggerDateTime,
        ScheduleState $state,
        Rule|null $rule = null,
        DateTimeImmutable|null $startDateTime = null
    ): void {
        $schedule = $this->schedules[ScheduleState::Pending->value][$identifier];
        $this->schedules[$state->value][$identifier] = new SimpleScheduleStoreEntry(
            $triggerDateTime,
            $schedule->message(),
            $rule,
            $startDateTime,
        );
        if ($state === ScheduleState::Pending) {
            return;
        }

        unset($this->schedules[ScheduleState::Pending->value][$identifier]);
    }

    /**
     * @psalm-return list<non-empty-string>
     */
    public function findPendingSchedules(DateTimeImmutable $date): array
    {
        $pending = [];
        foreach ($this->schedules[ScheduleState::Pending->value] as $identifier => $schedule) {
            if ($schedule->triggerDateTime() > $date) {
                continue;
            }

            $pending[] = $identifier;
        }

        return $pending;
    }

    public function deleteSchedule(string $identifier): void
    {
        unset($this->schedules[ScheduleState::Pending->value][$identifier]);
    }
}
