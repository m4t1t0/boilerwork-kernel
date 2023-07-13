<?php

declare(strict_types=1);

namespace Boilerwork\Support\ValueObjects\Address;

use Boilerwork\Foundation\ValueObjects\ValueObject;
use Boilerwork\Validation\Assert;

/**
 * @deprecated
 */
final class Street extends ValueObject
{
    private function __construct(
        private string $name,
        private ?string $number,
        private ?string $other1,
        private ?string $other2
    ) {
        Assert::lazy()->tryAll()
            ->that($name)
            ->notEmpty('Name must not be empty', 'streetName.notEmpty')
            ->verifyNow();
    }

    public static function fromValues(
        string $name,
        ?string $number,
        ?string $other1,
        ?string $other2
    ): self {
        return new self(
            name: $name,
            number: $number,
            other1: $other1,
            other2: $other2
        );
    }

    public function name(): string
    {
        return $this->name;
    }

    public function number(): ?string
    {
        return $this->number;
    }

    public function other1(): ?string
    {
        return $this->other1;
    }

    public function other2(): ?string
    {
        return $this->other2;
    }

    public function toString(): string
    {
        return sprintf('%s %s, %s %s', $this->name(), $this->number(), $this->other1(), $this->other2());
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name(),
            'number' => $this->number(),
            'other1' => $this->other1(),
            'other2' => $this->other2()
        ];
    }
}
