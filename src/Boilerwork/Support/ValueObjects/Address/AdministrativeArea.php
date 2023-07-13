<?php

declare(strict_types=1);

namespace Boilerwork\Support\ValueObjects\Address;

use Boilerwork\Foundation\ValueObjects\ValueObject;
use Boilerwork\Validation\Assert;

/**
 * @deprecated
 */
final class AdministrativeArea extends ValueObject
{
    private function __construct(
        private ?string $value
    ) {
        Assert::lazy()->tryAll()
            ->that($value)
            ->nullOr()
            ->string('Value must be a string', 'administrativeArea.invalidType')
            ->maxLength(128, 'Value must be 128 characters length', 'administrativeArea.invalidLength')
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
