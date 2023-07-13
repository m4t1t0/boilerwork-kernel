#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace Boilerwork\Support\Services\FeesCalculator\ValueObjects\Currency;

use Boilerwork\Foundation\ValueObjects\ValueObject;
use Boilerwork\Validation\Assert;

class Iso4217Code extends ValueObject
{
    public function __construct(
        public readonly string $value
    )
    {
        // var_dump(DataProvider::iso4217Codes());
        $value = mb_strtoupper($value);
        Assert::lazy()->tryAll()
            ->that($value)
            ->inArray(DataProvider::iso4217Codes(), 'Value must be a iso4217 code', 'currencyIso4217.invalid')
            ->verifyNow();
    }

    /**
     * @deprecated use toString()
     */
    public function toPrimitive(): string
    {
        return $this->value;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(ValueObject $object): bool
    {
        return $object instanceof self && $this->value === $object->toPrimitive();
    }
}
