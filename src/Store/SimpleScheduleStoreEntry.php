<?php

declare(strict_types=1);

namespace Brzuchal\Scheduler\Store;

use DateInterval;
use DateTimeImmutable;

final class SimpleScheduleStoreEntry implements ScheduleStoreEntry
{
    public function __construct(
        protected DateTimeImmutable $triggerDateTime,
        protected object $message,
        protected DateInterval|null $interval,
    ) {
    }

    public function triggerDateTime(): DateTimeImmutable
    {
        return $this->triggerDateTime;
    }

    public function message(): object
    {
        return $this->message;
    }

    public function interval(): DateInterval|null
    {
        return $this->interval;
    }
}
