<?php

declare(strict_types=1);

namespace Brzuchal\Scheduler\Store;

use DateInterval;
use DateTimeImmutable;

interface ScheduleStoreEntry
{
    public function triggerDateTime(): DateTimeImmutable;

    public function message(): object;

    public function interval(): DateInterval|null;
}
