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
    /** @psalm-var array<int, <string, SimpleScheduleStoreEntry>> */
    protected array $schedules;
    // phpcs:enable

    public function __construct()
    {
        $this->schedules = [
            ScheduleState::Completed->value => [],
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
        ScheduleState $state,
        DateTimeImmutable|null $triggerDateTime = null,
    ): void {
        foreach (ScheduleState::cases() as $case) {
            if (! array_key_exists($case->value, $this->schedules)) {
                continue;
            }

            if ($case === $state) {
                return;
            }

            $schedule = $this->schedules[$case->value][$identifier];
            assert($schedule instanceof SimpleScheduleStoreEntry);
            $this->schedules[$state->value][$identifier] = new SimpleScheduleStoreEntry(
                $triggerDateTime ?? $schedule->triggerDateTime(),
                $schedule->message(),
                $schedule->rule(),
                $schedule->startDateTime(),
            );
            unset($this->schedules[$case->value][$identifier]);
        }
    }

    /**
     * @psalm-return list<non-empty-string>
     */
    public function findPendingSchedules(
        DateTimeImmutable|null $beforeDateTime = null,
        int|null $limit = null,
    ): array {
        $pending = [];
        foreach ($this->schedules[ScheduleState::Pending->value] as $identifier => $schedule) {
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
        unset($this->schedules[ScheduleState::Pending->value][$identifier]);
    }
}
