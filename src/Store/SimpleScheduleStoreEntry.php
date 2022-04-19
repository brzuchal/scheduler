<?php

declare(strict_types=1);

namespace Brzuchal\Scheduler\Store;

use Brzuchal\RecurrenceRule\Rule;
use DateTimeImmutable;

final class SimpleScheduleStoreEntry implements ScheduleStoreEntry
{
    public function __construct(
        protected DateTimeImmutable $triggerDateTime,
        protected object $message,
        protected Rule|null $rule,
        protected DateTimeImmutable|null $startDateTime
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

    public function rule(): Rule|null
    {
        return $this->rule;
    }

    public function startDateTime(): DateTimeImmutable|null
    {
        return $this->startDateTime;
    }
}
