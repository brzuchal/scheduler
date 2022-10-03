<?php

declare(strict_types=1);

namespace Brzuchal\Scheduler\Tests\Store;

use Brzuchal\Scheduler\ScheduleState;
use Brzuchal\Scheduler\Store\PdoScheduleStore;
use Brzuchal\Scheduler\Store\ScheduleEntryNotFound;
use Brzuchal\Scheduler\Tests\Fixtures\FooMessage;
use DateTimeImmutable;
use PDO;
use PHPUnit\Framework\TestCase;

use function file_exists;
use function getcwd;
use function sprintf;
use function str_replace;
use function strlen;
use function touch;
use function unlink;

class PdoScheduleStoreTest extends TestCase
{
    public const IDENTIFIER = '3aa3d0ae-a2b9-4184-ad89-e625afb77026';
    protected PdoScheduleStore $store;
    protected PDO $pdo;
    protected string $tableName = PdoScheduleStore::MESSAGES_TABLE_NAME;

    public static function setUpBeforeClass(): void
    {
        $url = 'sqlite:test.sqlite';
        $databasePath = str_replace('sqlite:', getcwd(), $url);
        file_exists($databasePath) && unlink($databasePath);
        touch($databasePath);
    }

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite:test.sqlite');
//        $this->pdo = new PDO('sqlite::memory');
        $this->store = new PdoScheduleStore($this->pdo);
        $length = 1;
        foreach (ScheduleState::cases() as $scheduleState) {
            if (strlen($scheduleState->value) < $length) {
                continue;
            }

            $length = strlen($scheduleState->value);
        }

        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS {$this->tableName} (
    id VARCHAR(36) NOT NULL PRIMARY KEY,
    trigger_at DATETIME NOT NULL,
    serialized TEXT NOT NULL,
    rule TEXT,
    start_at DATETIME,
    state VARCHAR({$length}),
    created_at DATETIME
);
SQL;
        $this->pdo->exec($sql);
        $sql = <<<SQL
CREATE INDEX IF NOT EXISTS index_{$this->tableName}_trigger_state 
ON {$this->tableName}(trigger_at,state);
SQL;
        $this->pdo->exec($sql);
        $sql = <<<SQL
CREATE INDEX IF NOT EXISTS index_{$this->tableName}_state 
ON {$this->tableName}(state);
SQL;
        $this->pdo->exec($sql);
    }

    public function testNotFound(): void
    {
        $this->pdo->exec(sprintf(
            'DELETE FROM `%s`',
            $this->tableName,
        ));
        $this->expectException(ScheduleEntryNotFound::class);
        $this->store->findSchedule(self::IDENTIFIER);
    }

    /** @depends testNotFound */
    public function testInsert(): void
    {
        $triggerDateTime = new DateTimeImmutable('tomorrow');
        $this->store->insertSchedule(
            self::IDENTIFIER,
            $triggerDateTime,
            new FooMessage(),
        );
        $entry = $this->fetchEntry();
        $this->assertNotEmpty($entry);
        $this->assertNull($entry['rule']);
        $this->assertNull($entry['start_at']);
    }

    /** @depends testInsert */
    public function testFind(): void
    {
        $schedule = $this->store->findSchedule(self::IDENTIFIER);
        $this->assertInstanceOf(FooMessage::class, $schedule->message());
        $this->assertGreaterThan(new DateTimeImmutable('now'), $schedule->triggerDateTime());
        $this->assertNull($schedule->rule());
        $this->assertNull($schedule->startDateTime());
    }

    /** @depends testInsert */
    public function testNoPending(): void
    {
        $identifiers = $this->store->findPendingSchedules(new DateTimeImmutable('now'));
        $this->assertEmpty($identifiers);
    }

    /** @depends testNoPending */
    public function testUpdate(): void
    {
        $previousEntry = $this->fetchEntry();
        $this->assertNotEmpty($previousEntry);
        $previous = $previousEntry['trigger_at'];
        $this->store->updateSchedule(
            self::IDENTIFIER,
            ScheduleState::Completed,
            new DateTimeImmutable('yesterday'),
        );
        $entry = $this->fetchEntry();
        // phpcs:disable
        $this->assertNotEmpty($entry['trigger_at']);
        $this->assertNotEquals($previous, $entry['trigger_at']);
        $this->assertEquals(ScheduleState::Completed->value, $entry['state']);
    }

    /** @depends testUpdate */
    public function testPending(): void
    {
        $this->pdo->exec(sprintf(
            "DELETE FROM `%s`",
            $this->tableName,
        ));
        $this->store->insertSchedule(
            self::IDENTIFIER,
            new DateTimeImmutable('yesterday'),
            new FooMessage(),
        );
        $identifiers = $this->store->findPendingSchedules(new DateTimeImmutable('now'));
        $this->assertNotEmpty($identifiers);
        $this->assertContainsEquals(self::IDENTIFIER, $identifiers);
    }

    /** @depends testPending */
    public function testDelete(): void
    {
        $this->store->deleteSchedule(self::IDENTIFIER);
        $this->assertFalse($this->fetchEntry());
    }

    protected function fetchEntry(): mixed
    {
        $statement = $this->pdo->prepare(sprintf(
            "SELECT * FROM `%s` WHERE `id` = ?",
            $this->tableName,
        ));
        $statement->execute([self::IDENTIFIER]);

        return $statement->fetch();
    }
}
