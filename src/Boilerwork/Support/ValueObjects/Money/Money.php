#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace Boilerwork\Support\ValueObjects\Money;

use Boilerwork\Foundation\ValueObjects\ValueObject;
use Boilerwork\Validation\Assert;

abstract class Money extends ValueObject
{
    public function __construct(
        private float $amount,
        private Currency $currency
    ) {
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
        return sprintf('%s %s', $this->currency->toPrimitive(), $this->rounded());
    }

    public function amount(): float
    {
        return $this->amount;
    }

    public function rounded(): float
    {
        return (float)number_format($this->amount, $this->currency->precision());
    }

    public function currency(): Currency
    {
        return $this->currency;
    }
}
