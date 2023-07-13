#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace Boilerwork\Support\ValueObjects;

use Boilerwork\Foundation\ValueObjects\ValueObject;
use Boilerwork\Validation\Assert;

abstract class Url extends ValueObject
{
    public function __construct(
        private readonly string $value
    ) {
        Assert::lazy()->tryAll()
            ->that($value)
            ->notEmpty('Value must not be empty', 'url.notEmpty')
            ->url('Value must be a valid URL', 'url.invalidFormat')
            ->verifyNow();
    }

    public function scheme(): string
    {
        return parse_url($this->value, PHP_URL_SCHEME);
    }

    public function host(): string
    {
        return parse_url($this->value, PHP_URL_HOST);
    }

    public function port(): int
    {
        return parse_url($this->value, PHP_URL_PORT);
    }

    public function user(): string
    {
        return parse_url($this->value, PHP_URL_USER);
    }

    public function password(): string
    {
        return parse_url($this->value, PHP_URL_PASS);
    }

    public function path(): string
    {
        return parse_url($this->value, PHP_URL_PATH);
    }

    public function query(): string
    {
        return parse_url($this->value, PHP_URL_QUERY);
    }

    public function fragment(): string
    {
        return parse_url($this->value, PHP_URL_FRAGMENT);
    }

    /**
     * @deprecated use toString()
     */
    public function toPrimitive(): string
    {
        return $this->toString();
    }

    public function toString(): string
    {
        return $this->value;
    }
}
