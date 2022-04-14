<?php

declare(strict_types=1);

namespace Brzuchal\Scheduler;

// phpcs:disable
enum ScheduleState: string
{
    case Pending = 'pending';
    case InProgress = 'in-progress';
    case Completed = 'completed';
    case Rejected = 'rejected';
}
