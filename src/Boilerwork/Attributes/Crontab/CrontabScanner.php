#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace Boilerwork\Attributes\Crontab;

use App\Shared\Providers\MessagingProvider;
use Attribute;
use Boilerwork\Attributes\AbstractScanner;
use Boilerwork\Attributes\Routing\Route;
use Boilerwork\Persistence\Adapters\Redis\RedisAdapter;
use Boilerwork\Validation\Assert;
use OpenSwoole\Coroutine;
use OpenSwoole\Process;

/**
 * Scheduled Crons/Jobs are triggered immediately if they were never executed before and there is no block in Redis.
 */
final class CrontabScanner extends AbstractScanner
{
    protected const ATTRIBUTE_CLASS = Crontab::class;

    private array $tasks = [];

    private readonly RedisAdapter $redis;

    protected function processAttribute(\ReflectionAttribute $attribute, $parentClass = null): void
    {
        $this->tasks[] = [
            'arguments' => $attribute->getArguments(),
            'target' => $parentClass->getName(),
        ];
    }

    /**
     * Overwrite parent method to add this in a separate Process
     */
    public function scan(string $directory): void
    {
        $process = new \OpenSwoole\Process(function ($worker) use ($directory) {
            parent::scan($directory);
            go(function () {
                $this->run();
            });
        });

        $process->start();
    }

    private function run(): void
    {
        if(count($this->tasks) === 0)
        {
            return;
        }

        // No podemos inyectarlo en el constructor al estar en un proceso independiente.
        $this->redis = globalContainer()->get(RedisAdapter::class);

        while (true) {
            foreach ($this->tasks as $task) {

                $crontab = new Crontab(
                    interval: $task['arguments']['interval'],
                    at: $task['arguments']['at'] ?? null,
                    target: $task['target'],
                );

                $key = sprintf('crontab:%s:%s:%s', env('APP_ENV'), env('APP_NAME'), $crontab->target);

                $intervalSeconds = $this->getSecondsForInterval(interval: $crontab->interval, at: $crontab->at);

                // Obtiene un bloqueo en Redis y establece un TTL en la clave si no existe previamente.
                $lockAcquired = $this->redis->set($key, 1, ['nx', 'ex' => $intervalSeconds]);

                if ($lockAcquired) {
                    go(function () use ($crontab) {
                        try {
                            $instance = globalContainer()->get($crontab->target);
                            $instance->handle();
                        } catch (\Throwable $e) {
                            $errorMessage = "Error while executing task: " . $e->getMessage();
                            error($errorMessage);
                        }
                    });
                }
            }

            sleep(1);
        }
    }

    private function getSecondsForInterval(string $interval, string $at = null): int
    {
        return match ($interval) {
            Crontab::INTERVAL_EVERY_MINUTE => $this->getSecondsUntilNextMinute(),
            Crontab::INTERVAL_HOURLY => $this->getSecondsUntilNextHour(),
            Crontab::INTERVAL_DAILY,
            Crontab::INTERVAL_DAILY_AT => $this->getSecondsUntilNextDayAt(at: $at),
            Crontab::INTERVAL_WEEKLY => $this->getSecondsUntilNextMonday(),
            Crontab::INTERVAL_MONDAY,
            Crontab::INTERVAL_TUESDAY,
            Crontab::INTERVAL_WEDNESDAY,
            Crontab::INTERVAL_THURSDAY,
            Crontab::INTERVAL_FRIDAY,
            Crontab::INTERVAL_SATURDAY,
            Crontab::INTERVAL_SUNDAY => $this->getSecondsUntilNextDayOfTheWeek(dayOfTheWeek: $interval),
            default => throw new InvalidArgumentException("Invalid interval: $interval"),
        };
    }

    private function getSecondsUntilNextMinute(): int
    {
        $nextMinute = new \DateTime('1 minute');
        $nextMinute->setTime((int)$nextMinute->format('H'), (int)$nextMinute->format('i'), 0);

        $now = new \DateTime();

        return $nextMinute->getTimestamp() - $now->getTimestamp();
    }

    private function getSecondsUntilNextHour(): int
    {
        $nextHour = new \DateTime('1 hour');
        $nextHour->setTime((int)$nextHour->format('H'), 0, 0);
        $now = new \DateTime();

        return $nextHour->getTimestamp() - $now->getTimestamp();
    }

    /**
     * Calculate the number of seconds until the next occurrence of a specific day of the week.
     *
     * This function calculates the number of seconds between the current time and the next
     * occurrence of the specified day of the week. The day of the week should be provided
     * as a string, such as 'monday', 'tuesday', etc.
     *
     * @param string $dayOfTheWeek The day of the week for which to calculate the next occurrence.
     *                             This should be a string like 'monday', 'tuesday', etc.
     * @return int The number of seconds until the next occurrence of the specified day of the week.
     *
     * @throws \Exception If there is an error parsing the day of the week string.
     *
     * @example
     * // Get the number of seconds until next Monday.
     * $seconds = $this->getSecondsUntilNextDayOfTheWeek('monday');
     *
     * // Get the number of seconds until next Friday.
     * $seconds = $this->getSecondsUntilNextDayOfTheWeek('friday');
     */
    private function getSecondsUntilNextDayOfTheWeek(string $dayOfTheWeek): int
    {
        $nextDay = new \DateTime('next ' . $dayOfTheWeek);
        $now = new \DateTime();

        return $nextDay->getTimestamp() - $now->getTimestamp();
    }

    /**
     * Get remaining secondes until next Monday at 00:00
     */
    private function getSecondsUntilNextMonday(): int
    {
        $nextMonday = new \DateTime('next monday');
        $nextMonday->setTime(0, 0);
        $now = new \DateTime();

        return $nextMonday->getTimestamp() - $now->getTimestamp();
    }

    /**
     * Calculate the number of seconds until the next day at a given time.
     *
     * This function calculates the number of seconds between the current time
     * and the next day at a specific time. If no time is provided, it defaults
     * to midnight. The time should be provided in the format 'HH:MM'.
     *
     * @param string|null $at The time at which the next day should be calculated,
     *                        in the format 'HH:MM'. If null, defaults to '00:00'.
     * @return int The number of seconds until the next day at the specified time.
     *
     * @throws \Exception If there is an error parsing the time string.
     *
     * @example
     * // Get the number of seconds until the next day at midnight.
     * $seconds = $this->getSecondsUntilNextDayAt();
     *
     * // Get the number of seconds until the next day at 13:30.
     * $seconds = $this->getSecondsUntilNextDayAt('13:30');
     */
    private function getSecondsUntilNextDayAt($at = null): int
    {
        $nextMidnight = new \DateTime('tomorrow');

        if ($at) {
            list($hour, $minute) = explode(':', $at);
            $nextMidnight->setTime((int)$hour, (int)$minute, 0);
        } else {
            $nextMidnight->setTime(0, 0, 0);
        }

        $now = new \DateTime();

        return $nextMidnight->getTimestamp() - $now->getTimestamp();
    }
}
