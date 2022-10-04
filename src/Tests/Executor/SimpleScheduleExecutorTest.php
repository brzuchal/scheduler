<?php

namespace Brzuchal\Scheduler\Tests\Executor;

use Brzuchal\RecurrenceRule\Freq;
use Brzuchal\RecurrenceRule\Rule;
use Brzuchal\Scheduler\Executor\SimpleScheduleExecutor;
use Brzuchal\Scheduler\ScheduleState;
use Brzuchal\Scheduler\Store\InMemoryScheduleStore;
use Brzuchal\Scheduler\Store\ScheduleStore;
use Brzuchal\Scheduler\Store\SimpleScheduleStoreEntry;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use stdClass;
use UnexpectedValueException;

class SimpleScheduleExecutorTest extends TestCase
{
    public function testWithoutRecurrenceRule(): void
    {
        $message = new stdClass();
        $store = $this->createMock(ScheduleStore::class);
        $executor = new SimpleScheduleExecutor(
            $store,
            fn (object $dispatched) => $this->assertSame($message, $dispatched),
        );
        $store->expects($this->once())
            ->method('findSchedule')
            ->with('foo')
            ->willReturn(new SimpleScheduleStoreEntry(
                triggerDateTime: new DateTimeImmutable('yesterday'),
                message: $message,
                rule: null,
                startDateTime: null,
            ));
        $store->expects($this->exactly(2))
            ->method('updateSchedule')
            ->withConsecutive(
                ['foo', ScheduleState::InProgress],
                ['foo', ScheduleState::Completed],
            );
        $executor->execute('foo');
    }

    public function testWithRecurrenceRuleButNoStartDate(): void
    {
        $message = new stdClass();
        $store = $this->createMock(ScheduleStore::class);
        $executor = new SimpleScheduleExecutor(
            $store,
            fn (object $dispatched) => $this->assertSame($message, $dispatched),
        );
        $store->expects($this->once())
            ->method('findSchedule')
            ->with('foo')
            ->willReturn(new SimpleScheduleStoreEntry(
                triggerDateTime: new DateTimeImmutable('yesterday'),
                message: $message,
                rule: new Rule(freq: Freq::Daily, count: 2),
                startDateTime: null,
            ));
        $store->expects($this->exactly(1))
            ->method('updateSchedule')
            ->withConsecutive(
                ['foo', ScheduleState::InProgress],
            );
        $this->expectException(UnexpectedValueException::class);
        $executor->execute('foo');
    }

    public function testWithRecurrenceRuleAfterFirstOccurrence(): void
    {
        $message = new stdClass();
        $store = $this->createMock(ScheduleStore::class);
        $executor = new SimpleScheduleExecutor(
            $store,
            fn (object $dispatched) => $this->assertSame($message, $dispatched),
        );
        $store->expects($this->once())
            ->method('findSchedule')
            ->with('foo')
            ->willReturn(new SimpleScheduleStoreEntry(
                triggerDateTime: new DateTimeImmutable('yesterday'),
                message: $message,
                rule: new Rule(freq: Freq::Daily, count: 2),
                startDateTime: new DateTimeImmutable('today'),
            ));
        $store->expects($this->exactly(2))
            ->method('updateSchedule')
            ->withConsecutive(
                ['foo', ScheduleState::InProgress],
                ['foo', ScheduleState::Pending],
            );
        $executor->execute('foo');
    }

    public function testWithRecurrenceRuleAfterLastOccurrence(): void
    {
        $message = new stdClass();
        $store = $this->createMock(ScheduleStore::class);
        $executor = new SimpleScheduleExecutor(
            $store,
            fn (object $dispatched) => $this->assertSame($message, $dispatched),
        );
        $store->expects($this->once())
            ->method('findSchedule')
            ->with('foo')
            ->willReturn(new SimpleScheduleStoreEntry(
                triggerDateTime: new DateTimeImmutable('yesterday'),
                message: $message,
                rule: new Rule(freq: Freq::Daily, count: 1),
                startDateTime: new DateTimeImmutable('today'),
            ));
        $store->expects($this->exactly(2))
            ->method('updateSchedule')
            ->withConsecutive(
                ['foo', ScheduleState::InProgress],
                ['foo', ScheduleState::Completed],
            );
        $executor->execute('foo');
    }
}
