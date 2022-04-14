<?php

declare(strict_types=1);

namespace Brzuchal\Scheduler;

// phpcs:disable
enum ScheduleType: string
{
    case TriggerTime = 'trigger-time';
    case FixedDelay = 'fixed-delay';
    case FixedRate = 'fixed-rate';
    case RecurrentRule = 'recurrent-rule';
}
