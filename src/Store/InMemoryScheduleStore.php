<?php

declare(strict_types=1);

namespace Brzuchal\Scheduler\Store;

use Brzuchal\RecurrenceRule\Rule;
use Brzuchal\Scheduler\ScheduleState;
use DateTimeImmutable;

use function array_key_exists;
use function assert;

final class InMemoryScheduleStore implements ScheduleStore
{
    // phpcs:disable
    /** @psalm-var array<non-empty-string, SimpleScheduleStoreEntry> */
    protected array $schedules = [];
    /** @psalm-var array<non-empty-string, ScheduleState> */
    protected array $statuses = [];
    // phpcs:enable

    /**
     * @psalm-param non-empty-string $identifier
     */
    public function findSchedule(string $identifier): ScheduleStoreEntry
    {
        return clone $this->schedules[$identifier];
    }

    /**
     * @psalm-param non-empty-string $identifier
     */
    public function insertSchedule(
        string $identifier,
        DateTimeImmutable $triggerDateTime,
        object $message,
        Rule|null $rule = null,
        DateTimeImmutable|null $startDateTime = null,
    ): void {
        $this->schedules[$identifier] = new SimpleScheduleStoreEntry(
            $triggerDateTime,
            $message,
            $rule,
            $startDateTime,
        );
        $this->statuses[$identifier] = ScheduleState::Pending;
    }

    /**
     * @psalm-param non-empty-string $identifier
     */
    public function updateSchedule(
        string $identifier,
        ScheduleState $state,
        DateTimeImmutable|null $triggerDateTime = null,
        Rule|null $rule = null,
    ): void {
        $schedule = $this->schedules[$identifier];
        assert($schedule instanceof SimpleScheduleStoreEntry);
        $this->schedules[$identifier] = new SimpleScheduleStoreEntry(
            $triggerDateTime ?? $schedule->triggerDateTime(),
            $schedule->message(),
            $rule ?? $schedule->rule(),
            $schedule->startDateTime(),
        );
        $this->statuses[$identifier] = $state;
    }

    /**
     * @psalm-return list<non-empty-string>
     */
    public function findPendingSchedules(
        DateTimeImmutable|null $beforeDateTime = null,
        int|null $limit = null,
    ): array {
        $pending = [];
        foreach ($this->statuses as $identifier => $state) {
            if ($state !== ScheduleState::Pending) {
                continue;
            }

            $schedule = $this->schedules[$identifier];
            if ($beforeDateTime !== null && $schedule->triggerDateTime() > $beforeDateTime) {
                continue;
            }

            if ($limit !== null && --$limit < 0) {
                break;
            }

            $pending[] = $identifier;
        }

        return $pending;
    }

    public function deleteSchedule(string $identifier): void
    {
        unset($this->schedules[$identifier], $this->statuses[$identifier]);
    }
}
