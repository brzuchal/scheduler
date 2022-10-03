<?php

declare(strict_types=1);

namespace Brzuchal\Scheduler\Tests;

use Brzuchal\RecurrenceRule\Freq;
use Brzuchal\RecurrenceRule\Rule;
use Brzuchal\Scheduler\MessageScheduler;
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

    public function testReschedule(): void
    {
        $scheduler = new MessageScheduler($this->store);
        $triggerAt = (new DateTimeImmutable('now'))->add(new DateInterval('PT1S'));
        $message = new FooMessage();
        $startDateTime = new DateTimeImmutable('today');
        $token = $scheduler->schedule($triggerAt, $message, startDateTime: $startDateTime);
        $rule = new Rule(Freq::Yearly);
        $scheduler->reschedule($token, $triggerAt, $rule);
        sleep(1);
        $schedules = $this->store->findPendingSchedules(new DateTimeImmutable('now'));
        $this->assertNotEmpty($schedules);
        $this->assertContainsOnly('string', $schedules);
        $schedule = $this->store->findSchedule($schedules[0]);
        $this->assertEquals($triggerAt, $schedule->triggerDateTime());
        $this->assertEquals($message, $schedule->message());
        $this->assertEquals($rule, $schedule->rule());
        $this->assertEquals($startDateTime, $schedule->startDateTime());
    }
}
