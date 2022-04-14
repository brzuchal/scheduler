<?php

declare(strict_types=1);

namespace Brzuchal\Scheduler;

use Brzuchal\Scheduler\Store\ScheduleStore;
use DateInterval;
use DateTimeImmutable;
use Exception;

use function assert;
use function hash;
use function random_bytes;

final class MessageScheduler
{
    public function __construct(
        protected ScheduleStore $store,
    ) {
    }

    /**
     * @throws PastSchedulingNotPossible
     * @throws Exception
     */
    public function schedule(
        DateTimeImmutable $triggerDateTime,
        object $message,
        DateInterval|null $interval = null,
    ): ScheduleToken {
        if ($triggerDateTime < (new DateTimeImmutable('now'))) {
            throw PastSchedulingNotPossible::create($triggerDateTime);
        }

        $identifier = hash('sha256', random_bytes(1024));
        assert(! empty($identifier));
        $this->store->insertSchedule($identifier, $triggerDateTime, $message, $interval);

        return new ScheduleToken($identifier);
    }

    /**
     * @throws PastSchedulingNotPossible
     */
    public function reschedule(
        ScheduleToken $token,
        DateTimeImmutable $triggerDateTime,
        object $message,
        DateInterval|null $interval = null,
    ): ScheduleToken {
        $this->cancel($token);

        return $this->schedule($triggerDateTime, $message, $interval);
    }

    public function cancel(ScheduleToken $token): void
    {
        $this->store->deleteSchedule($token->tokenId);
    }
}
