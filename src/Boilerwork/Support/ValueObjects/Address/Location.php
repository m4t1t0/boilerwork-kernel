<?php

declare(strict_types=1);

namespace Boilerwork\Support\ValueObjects\Address;

use Boilerwork\Foundation\ValueObjects\ValueObject;
use Boilerwork\Support\ValueObjects\Identity;
use Boilerwork\Validation\Assert;

/**
 * @deprecated
 */
final class Location extends ValueObject
{
    private function __construct(
        private Identity $id,

    ) {
        Assert::lazy()->tryAll()
            ->that($id->toString())
            ->uuid('Value must be a valid UUID', 'locationId.invalidValue')
            ->verifyNow();
    }

    public static function fromId(string $id): self
    {
        return new self(id: Identity::fromString($id));
    }

    public function toString(): string
    {
        return $this->id->toString();
    }
}
