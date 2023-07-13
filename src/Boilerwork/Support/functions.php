#!/usr/bin/env php
<?php

declare(strict_types=1);

use Boilerwork\Container\IsolatedContainer;
use Boilerwork\Support\Logs\Logger;
use Boilerwork\Messaging\MessagePublisher;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Dumper\ServerDumper;

if (!function_exists('env')) {
    function env(string $name, mixed $defaultValue = null): mixed
    {
        if (isset($_ENV[$name])) {
            return $_ENV[$name];
        } else if (getenv($name) !== false) {
            return getenv($name);
        }

        return $defaultValue;
    }
}

if (!function_exists('getMemoryStatus')) {
    // Monitor memory
    function getMemoryStatus(): void
    {
        $fh = fopen('/proc/meminfo', 'r');

        if ($fh) {
            $memTotal = 0;
            while ($line = fgets($fh)) {
                $pieces = array();

                if (preg_match('/^MemTotal:\s+(\d+)\skB$/', $line, $pieces)) {
                    $memTotal = $pieces[1];
                    break;
                }
            }
            fclose($fh);

            printf("\n Memory Total: %s kB\n Memory Usage: %s kB\n Memory Peak: %s kB\n\n", $memTotal, memory_get_usage() / 1024, memory_get_peak_usage(true) / 1024);
        }
    }
}

if (!function_exists('container')) {
    /**
     * Return Container instance to be used in local/isolated requests/jobs/messages
     * @return IsolatedContainer
     */
    function container()
    {
        return (\Boilerwork\Container\Container::getInstance())->getIsolatedContainer();
    }
}

if (!function_exists('globalContainer')) {
    /**
     * Return Global Container instance with shared data across application memory
     *
     **/
    function globalContainer()
    {
        return \Boilerwork\Container\Container::getInstance();
    }
}

if (!function_exists('eventsPublisher')) {
    function eventsPublisher(): MessagePublisher
    {
        return MessagePublisher::getInstance();
    }
}

if (!function_exists('base_path')) {
    /**
     * @return string Path from /src
     **/
    function base_path(?string $path = ''): string
    {
        // Defined in Application Start
        return BASE_PATH . $path;
    }
}

if (!function_exists('error')) {
    function error(string|Stringable|array $message, string $exception = \Throwable::class, ?string $channel = 'error'): void
    {
        $debug = env('APP_DEBUG') ?? false;
        if (boolval($debug) === false) {
            return;
        }

        Logger::error(message: $message, exception: $exception, channel: $channel);
    }
}

if (!function_exists('logger')) {
    function logger(string|Stringable|array $message, string $mode = 'DEBUG', string $channel = 'default'): void
    {
        $debug = env('APP_DEBUG') ?? false;
        if (boolval($debug) === false) {
            return;
        }

        Logger::logger(message: $message, mode: $mode, channel: $channel);
    }
}

if (!function_exists('attrsToSnakeCase')) {

    function toSnakeCase($string)
    {
        if (!is_string($string)) {
            return $string;
        }
        return strtolower(preg_replace('/(?<=[a-z])[A-Z]/', '_$0', $string));
    }

    function objectToArray($object)
    {
        return json_decode(json_encode($object), true);
    }

    function attrsToSnakeCase($input)
    {
        if (is_string($input)) {
            return toSnakeCase($input);
        }

        if (is_object($input)) {
            $input = objectToArray($input);
        }

        $result = [];
        foreach ($input as $key => $value) {
            $snakeKey = toSnakeCase($key);

            if (is_array($value) || is_object($value)) {
                $value = attrsToSnakeCase((array) $value);
            }

            $result[$snakeKey] = $value;
        }

        return $result;
    }
}

if (!function_exists('qd')) {
    function qd(mixed $var, bool|string $output = true): ?string
    {
        $cloner = new VarCloner();
        $dumper = new CliDumper();

        if ($host = env('VAR_DUMPER_SERVER')){
            $dumper = new ServerDumper($host, $dumper);
        }

        return $dumper->dump($cloner->cloneVar($var), $output);
    }
}