# Message Scheduler

![Tests](https://github.com/brzuchal/scheduler/actions/workflows/continous-integration.yml/badge.svg)

---

## A message to Russian 🇷🇺 people

If you currently live in Russia, please read [this message](./ToRussianPeople.md).

## Purpose

Message Scheduler implements persistent storage for any kind of message objects
for all the idle time before it actually should be dispatched for handling.
Dispatching can happen at exact trigger time or using recurrence rule which
determines a set of times when the execution mechanism trigger the message.

[![SWUbanner](https://raw.githubusercontent.com/vshymanskyy/StandWithUkraine/main/banner2-direct.svg)](https://github.com/vshymanskyy/StandWithUkraine/blob/main/docs/README.md)

## Install

```shell
composer require brzuchal/scheduler
```

## Usage

```php
use Brzuchal\Scheduler\Executor\SimpleScheduleExecutor;
use Brzuchal\Scheduler\MessageScheduler;
use Brzuchal\Scheduler\Store\PdoScheduleStore;

$store = new PdoScheduleStore(new PDO('sqlite:scheduler.sqlite'));
$scheduler = new MessageScheduler($store);
$executor = new SimpleScheduleExecutor($store, var_dump(...));

// Scheduling message
$scheduler->schedule(new DateTimeImmutable('tomorrow 09:00:00'), new \FooMessage());

// Execute all pending messages
$dateTime = new DateTimeImmutable('now');
foreach ($store->findPendingSchedules($dateTime) as $identifier) {
    $executor->execute($identifier);
}
```

> *Note!* PDO store requires manual setup depending on database platform used.
> See [schema.sql](schema.sql) for example schema.

---

## License

MIT License

Copyright (c) 2022 Michał Marcin Brzuchalski

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
