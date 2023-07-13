#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace Boilerwork\Support\ValueObjects;

use Boilerwork\Foundation\ValueObjects\ValueObject;
use Boilerwork\Validation\Assert;
use Symfony\Component\Uid\Uuid as UuidImplementation;

/**
 *  Creates UUID using Symfony\Polyfill implementation, which turns out to be faster than pecl extension.
 **/
abstract class Uuid extends ValueObject
{
    public function __construct(
        protected string $value
    ) {
        Assert::lazy()->tryAll()
            ->that($value)
            ->notEmpty('Value must not be empty', 'uuid.notEmpty')
            ->uuid('Value must be a valid UUID', 'uuid.invalidFormat')
            ->verifyNow();

        $this->value = strtolower($value);
    }

    /**
     * Generate new UUID v7 value object
     **/
    public static function create(): static
    {
        return new static(UuidImplementation::v7()->toRfc4122());
    }

    /**
     * Create new Identity from String
     **/
    public static function fromString(string $uuid): static
    {
        return new static($uuid);
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
