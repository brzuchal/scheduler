<?php

declare(strict_types=1);

namespace Brzuchal\Scheduler\Tests;

use Brzuchal\Scheduler\MessageScheduler;
use Brzuchal\Scheduler\PastSchedulingNotPossible;
use Brzuchal\Scheduler\Store\InMemoryScheduleStore;
use Brzuchal\Scheduler\Store\ScheduleStore;
use Brzuchal\Scheduler\Tests\Fixtures\FooMessage;
use DateInterval;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

use function sleep;

class MessageSchedulerTest extends TestCase
{
    protected ScheduleStore $store;

    protected function setUp(): void
    {
        $this->store = new InMemoryScheduleStore();
    }

    public function testCreation(): void
    {
        $scheduler = new MessageScheduler($this->store);
        $now = new DateTimeImmutable('now');

        $scheduler->schedule($now->add(new DateInterval('PT1S')), new FooMessage());
        $this->assertEmpty($this->store->findPendingSchedules($now));
        sleep(1);
        $schedules = $this->store->findPendingSchedules(new DateTimeImmutable('now'));
        $this->assertNotEmpty($schedules);
        $this->assertContainsOnly('string', $schedules);
    }

    public function testCreationNotPossible(): void
    {
        $this->expectException(PastSchedulingNotPossible::class);
        $scheduler = new MessageScheduler($this->store);
        $scheduler->schedule(new DateTimeImmutable('yesterday'), new FooMessage());
    }

    public function testFetchPending(): void
    {
        $scheduler = new MessageScheduler($this->store);
        $triggerAt = (new DateTimeImmutable('now'))->add(new DateInterval('PT1S'));
        $message = new FooMessage();
        $scheduler->schedule($triggerAt, $message);
        sleep(1);
        $schedules = $this->store->findPendingSchedules(new DateTimeImmutable('now'));
        $this->assertNotEmpty($schedules);
        $this->assertContainsOnly('string', $schedules);
        $schedule = $this->store->findSchedule($schedules[0]);
        $this->assertEquals($triggerAt, $schedule->triggerDateTime());
        $this->assertEquals($message, $schedule->message());
    }
}
