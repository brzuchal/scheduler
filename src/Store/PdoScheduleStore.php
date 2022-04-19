<?php

declare(strict_types=1);

namespace Brzuchal\Scheduler\Store;

use Brzuchal\RecurrenceRule\Rule;
use Brzuchal\RecurrenceRule\RuleFactory;
use Brzuchal\Scheduler\ScheduleState;
use DateTimeImmutable;
use Exception;
use PDO;

use function array_map;
use function assert;
use function is_string;
use function serialize;
use function sprintf;
use function unserialize;

final class PdoScheduleStore implements ScheduleStore
{
    public const DEFAULT_EXECUTIONS_TABLE_NAME = 'schedule_exec';
    public const DEFAULT_DATA_TABLE_NAME = 'schedule_data';

    public function __construct(
        protected PDO $pdo,
        protected string $executionTableName = self::DEFAULT_EXECUTIONS_TABLE_NAME,
        protected string $dataTableName = self::DEFAULT_DATA_TABLE_NAME,
    ) {
    }

    /**
     * @throws ScheduleEntryNotFound
     * @throws Exception
     */
    public function findSchedule(string $identifier): ScheduleStoreEntry
    {
        $sql = sprintf(
            'SELECT `trigger_at`, `serialized`, `rule`, `start_at` FROM %s WHERE `id` = ?',
            $this->dataTableName,
        );
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$identifier]);
        $entry = $stmt->fetch(PDO::FETCH_ASSOC);

        if (empty($entry)) {
            throw new ScheduleEntryNotFound('not found'); // TODO: make static factory method
        }

        [
            'trigger_at' => $triggerAt,
            'serialized' => $serialized,
            'rule' => $rule,
            'start_at' => $startAt,
        ] = $entry;
        assert(is_string($triggerAt));
        assert(is_string($serialized));
        assert((! empty($rule) && is_string($rule)) || $rule === null);
        assert((! empty($startAt) && is_string($startAt)) || $startAt === null);

        return new SimpleScheduleStoreEntry(
            new DateTimeImmutable($triggerAt),
            unserialize($serialized),
            $rule ? RuleFactory::fromString($rule) : null,
            $startAt ? new DateTimeImmutable($startAt) : null,
        );
    }

    public function insertSchedule(
        string $identifier,
        DateTimeImmutable $triggerDateTime,
        object $message,
        Rule|null $rule = null,
        DateTimeImmutable|null $startDateTime = null,
    ): void {
        $sql = sprintf(
            'INSERT INTO %s (`id`, `trigger_at`, `serialized`, `rule`, `start_at`, `state`) VALUES (?, ?, ?, ?, ?, ?)',
            $this->dataTableName,
        );
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $identifier,
            $triggerDateTime,
            serialize($message),
            $rule?->toString(),
            $startDateTime,
            ScheduleState::Pending->value,
        ]);
    }

    public function updateSchedule(
        string $identifier,
        DateTimeImmutable $triggerDateTime,
        ScheduleState $state,
        Rule|null $rule = null,
        DateTimeImmutable|null $startDateTime = null
    ): void {
        $sql = sprintf(
            'UPDATE %s SET `trigger_at` = ?, `state` = ?, `rule` = ?, `start_at` = ? WHERE `id` = ?',
            $this->dataTableName,
        );
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $triggerDateTime,
            $state->value,
            $rule?->toString(),
            $startDateTime,
            $identifier,
        ]);
    }

    public function deleteSchedule(string $identifier): void
    {
        $sql = sprintf(
            'DELETE FROM %s WHERE `id` = ?',
            $this->dataTableName,
        );
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$identifier]);
    }

    /**
     * @psalm-return list<non-empty-string>
     *
     * @psalm-suppress MoreSpecificReturnType
     */
    public function findPendingSchedules(DateTimeImmutable $date): iterable
    {
        $sql = sprintf(
            'SELECT `id` FROM %s WHERE `trigger_at` < ? AND `state` = ?',
            $this->dataTableName,
        );
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $date,
            ScheduleState::Pending->value,
        ]);
        $pending = [];
        while ($entry = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $pending[] = $entry;
        }

        /** @psalm-suppress LessSpecificReturnStatement */
        return array_map(static fn (array $entry): string => (string) $entry['id'], $pending);
    }
}
