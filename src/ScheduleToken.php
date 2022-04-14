<?php

declare(strict_types=1);

namespace Brzuchal\Scheduler;

final class ScheduleToken
{
    public function __construct(
        /** @psalm-var non-empty-string */
        public readonly string $tokenId,
    ) {
    }
}
