<?php

declare(strict_types=1);

namespace Boilerwork\Support\ValueObjects\Geo\Country;

use Boilerwork\Foundation\ValueObjects\ValueObject;
use Boilerwork\Validation\Assert;

class Iso31661Alpha2 extends ValueObject
{
    private function __construct(
        private string $value
    ) {
        Assert::lazy()->tryAll()
            ->that($value)
            ->notEmpty('Value must not be empty', 'iso31661Alpha2.notEmpty')
            ->satisfy(function ($value) {
                return Iso31661Alpha2CodeProvider::tryFrom($value) !== null;
            }, 'Value must be a valid ISO-31661 Alpha-2 code', 'iso31661Alpha2.invalidFormat')
            ->verifyNow();
    }

    public static function fromString(string $value): self
    {
        return new self(value: $value);
    }

    public function toString(): string
    {
        return $this->value;
    }
}
