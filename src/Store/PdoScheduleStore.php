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
use function implode;
use function is_string;
use function serialize;
use function sprintf;
use function unserialize;

final class PdoScheduleStore implements ScheduleStore
{
    public const MESSAGES_TABLE_NAME = 'schedule_data';

    public function __construct(
        protected PDO $pdo,
        protected string $messagesTableName = self::MESSAGES_TABLE_NAME,
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
            $this->messagesTableName,
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
        $stmt = $this->pdo->prepare(sprintf(
            'INSERT INTO %s (`id`, `trigger_at`, `serialized`, `rule`, `start_at`, `state`) VALUES (?, ?, ?, ?, ?, ?)',
            $this->messagesTableName,
        ));
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
        ScheduleState $state,
        DateTimeImmutable|null $triggerDateTime = null,
    ): void {
        $set = ['`state` = ?'];
        $params = [$state->value];
        if ($triggerDateTime) {
            $set[] = '`trigger_at` = ?';
            $params[] = $triggerDateTime;
        }

        $stmt = $this->pdo->prepare(sprintf(
            'UPDATE %s SET %s WHERE `id` = ?',
            $this->messagesTableName,
            implode(', ', $set),
        ));
        $stmt->execute($params + [$identifier]);
    }

    public function deleteSchedule(string $identifier): void
    {
        $sql = sprintf(
            'DELETE FROM %s WHERE `id` = ?',
            $this->messagesTableName,
        );
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$identifier]);
    }

    /**
     * @psalm-return list<non-empty-string>
     *
     * @psalm-suppress MoreSpecificReturnType
     */
    public function findPendingSchedules(
        DateTimeImmutable|null $beforeDateTime = null,
        int|null $limit = null,
    ): array {
        $where = ['`state` = ?'];
        $params = [ScheduleState::Pending->value];
        if ($beforeDateTime !== null) {
            $where[] = '`trigger_at` < ?';
            $params[] = $beforeDateTime;
        }

        $sql = sprintf(
            'SELECT `id` FROM %s WHERE %s',
            $this->messagesTableName,
            implode(' AND ', $where),
        );
        if ($limit > 0) {
            $sql .= ' LIMIT ?';
            $params[] = $limit;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $pending = [];
        while ($entry = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $pending[] = $entry;
        }

        /** @psalm-suppress LessSpecificReturnStatement */
        return array_map(static fn (array $entry): string => (string) $entry['id'], $pending);
    }
}
