<?php

declare(strict_types=1);

namespace Brzuchal\Scheduler\Store;

use Brzuchal\RecurrenceRule\Rule;
use DateTimeImmutable;

interface ScheduleStoreEntry
{
    public function triggerDateTime(): DateTimeImmutable;

    public function message(): object;

    public function rule(): Rule|null;

    public function startDateTime(): DateTimeImmutable|null;
}
