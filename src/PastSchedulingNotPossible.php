<?php

declare(strict_types=1);

namespace Brzuchal\Scheduler;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;

use function sprintf;

final class PastSchedulingNotPossible extends Exception
{
    public static function create(DateTimeImmutable $triggerDateTime): self
    {
        return new self(sprintf(
            'Scheduling message in the past is not possible, tried with: %s',
            $triggerDateTime->format(DateTimeInterface::ISO8601),
        ));
    }
}
