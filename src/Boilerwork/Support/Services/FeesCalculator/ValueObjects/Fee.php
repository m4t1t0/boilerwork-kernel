#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace Boilerwork\Support\Services\FeesCalculator\ValueObjects;

use Boilerwork\Foundation\ValueObjects\ValueObject;
use Boilerwork\Validation\Assert;

class Fee extends ValueObject
{

    public function __construct(
        private float $value,
        private string $type
    )
    {
        $validation = Assert::lazy()->tryAll()
            ->that($type)
            ->choice(['percent', 'total'])
            ->that((float) \bcmul((string) $value,(string) 100, 2))
            ->integerish('Value must have a maximum of 2 decimal places', 'fee.decimalExceed')
            ->that($value)
            ->greaterOrEqualThan(0, 'Value must be greater than or equal to 0', 'fee.minValue');

        if ($type === 'percent') {
            $validation = $validation->lessOrEqualThan(100, 'Value must be less than or equal to 100', 'fee.maxValue');
        }
        $validation->verifyNow();
    }

    public function value(): float
    {
        return $this->value;
    }

    public function type(): string
    {
        return $this->type;
    }

    /**
     * @deprecated use toFloat()
     */
    public function toPrimitive(): float
    {
        return $this->toFloat();
    }

    public function toFloat(): float
    {
        return $this->value;
    }

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'type' => $this->type
        ];
    }
}
