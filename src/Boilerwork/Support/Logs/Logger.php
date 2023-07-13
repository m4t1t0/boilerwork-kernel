<?php

declare(strict_types=1);

namespace Boilerwork\Support\Logs;

use DateTimeImmutable;
use Stringable;

class Logger
{
    private static ?string $PATH_LOGGER = 'php://stdout';
    private static ?string $PATH_ERROR = 'php://stderr';

    public static function setLoggerOutput(?string $path = null)
    {
        self::$PATH_LOGGER = $path;
    }

    public static function setErrorOutput(?string $path = null)
    {
        self::$PATH_ERROR = $path;
    }

    public static function error(string|Stringable|array $message, ?string $path = null, string $exception = \Exception::class, ?string $channel = 'error'): void
    {
        $path = $path ?? self::$PATH_ERROR;

        if (empty($path)) {
            return;
        }

        $timestamp = new DateTimeImmutable();
        $message = is_array($message) ? json_encode($message) : ((method_exists($message, '__toString')) ? $message->__toString() : $message);
        $message = '[' . $timestamp->format(DateTimeImmutable::ATOM) . '] ' . strtoupper($exception) . ' ' . $message . PHP_EOL;

        self::write($path, $message, $channel, $timestamp);
    }

    public static function logger(string|Stringable|array $message, ?string $path = null, string $mode = 'DEBUG', string $channel = 'default'): void
    {
        $path = $path ?? self::$PATH_LOGGER;

        if (empty($path)) {
            return;
        }

        $timestamp = new DateTimeImmutable();
        $message = is_array($message) ? json_encode($message) : ((method_exists($message, '__toString')) ? $message->__toString() : $message);
        $message = '[' . $timestamp->format(DateTimeImmutable::ATOM) . '] ' . strtoupper($mode) . ' ' . $message . PHP_EOL;

        self::write($path, $message, $channel, $timestamp);
    }

    private static function interpolate(string $string, array $parameters = []): string
    {
        return preg_replace_callback('@\{([^}]+)\}@', fn($matches) => $parameters[$matches[1]] ?? '', $string);
    }

    private static function write( mixed $path, mixed $message, string $channel, ?DateTimeImmutable $date=null): void
    {
        $date = $date ?? new DateTimeImmutable();

        if (str_starts_with($path, 'php://')) {
            $fp = fopen($path, 'w');
        } else {
            $fp = fopen(base_path(self::interpolate($path, [
                'channel' => $channel,
                'date' => $date->format('Y-m-d')])), 'a');
        }

        stream_set_blocking($fp, false);

        if (flock($fp, LOCK_EX)) {
            fwrite($fp, $message);
        }
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}
