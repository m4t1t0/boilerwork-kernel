#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace Boilerwork\Support\ValueObjects;

use Boilerwork\Foundation\ValueObjects\ValueObject;
use Boilerwork\Validation\Assert;
use libphonenumber\PhoneNumber as LibPhoneNumber;

/**
 * Depends on: https://github.com/giggsey/libphonenumber-for-php
 **/
final class PhoneNumber extends ValueObject
{
    /**
     * @see https://www.itu.int/rec/T-REC-E.164/es
     **/
    public function __construct(
        private readonly string $number,
    ) {
        Assert::lazy()->tryAll()
            ->that($number)
            ->notEmpty(
                'Must not be empty number',
                'phoneNumber.emptyValue'
            )
            ->e164(
                'Must be a valid E164 format number',
                'phoneNumber.invalidValue'
            )
            ->verifyNow();
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
        return $this->number;
    }
}
