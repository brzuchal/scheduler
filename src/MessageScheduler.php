<?php

declare(strict_types=1);

namespace Brzuchal\Scheduler;

use Brzuchal\RecurrenceRule\Rule;
use Brzuchal\Scheduler\Store\ScheduleStore;
use Brzuchal\Scheduler\Store\SetupableScheduleStore;
use DateTimeImmutable;
use Exception;
use Ramsey\Uuid\Uuid;

use function assert;

final class MessageScheduler
{
    public function __construct(
        protected ScheduleStore $store,
    ) {
    }

    /**
     * @throws Exception
     */
    public function schedule(
        DateTimeImmutable $triggerDateTime,
        object $message,
        Rule|null $rule = null,
        DateTimeImmutable|null $startDateTime = null,
    ): ScheduleToken {
        $identifier = Uuid::uuid4()->toString();
        if ($this->store instanceof SetupableScheduleStore) {
            $this->store->setup();
        }

        $this->store->insertSchedule(
            $identifier,
            $triggerDateTime,
            $message,
            $rule,
            $startDateTime,
        );

        return new ScheduleToken($identifier);
    }

    public function reschedule(
        ScheduleToken $token,
        DateTimeImmutable $triggerDateTime,
        object $message,
        Rule|null $rule = null,
        DateTimeImmutable|null $startDateTime = null,
    ): ScheduleToken {
        $this->cancel($token);

        return $this->schedule(
            $triggerDateTime,
            $message,
            $rule,
            $startDateTime,
        );
    }

    public function update(
        ScheduleToken $token,
        Rule|null $rule,
        DateTimeImmutable|null $startDateTime = null,
    ): void {
        if ($this->store instanceof SetupableScheduleStore) {
            $this->store->setup();
        }

        $schedule = $this->store->findSchedule($token->tokenId);
        $this->store->updateSchedule(
            $token->tokenId,
            $schedule->triggerDateTime(),
            ScheduleState::Pending,
            $rule,
            $startDateTime,
        );
    }

    public function cancel(ScheduleToken $token): void
    {
        if ($this->store instanceof SetupableScheduleStore) {
            $this->store->setup();
        }

        $this->store->deleteSchedule($token->tokenId);
    }
}
