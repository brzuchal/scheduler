<?php

declare(strict_types=1);

namespace Brzuchal\Scheduler;

use Brzuchal\RecurrenceRule\Rule;
use Brzuchal\Scheduler\Store\ScheduleStore;
use DateTimeImmutable;
use Exception;
use Ramsey\Uuid\Uuid;

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
        Rule|null $rule = null,
    ): void {
        $entry = $this->store->findSchedule($token->tokenId);
        $this->store->updateSchedule(
            identifier: $token->tokenId,
            state: ScheduleState::Pending,
            triggerDateTime: $triggerDateTime,
            rule: $rule ?? $entry->rule(),
        );
    }

    public function cancel(ScheduleToken $token): void
    {
        $this->store->deleteSchedule($token->tokenId);
    }
}
