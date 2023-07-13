#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace Boilerwork\Support\ValueObjects;

use Boilerwork\Foundation\ValueObjects\ValueObject;
use Boilerwork\Validation\Assert;
use libphonenumber\PhoneNumberUtil;

/**
 * Depends on: https://github.com/giggsey/libphonenumber-for-php
 **/
final class PhonePrefix extends ValueObject
{
    private function __construct(
        private readonly ?string $value
    ) {
    }

    /**
     * @example: PhonePrefix::fromPrefix('44') PhonePrefix::fromPrefix('+34'),
     */
    public static function fromCountryCallingCode(string|null $value): static
    {
        if ($value) {
            $phonePrefixPattern = '/^[0-9\+\-\s]+$/';
            $phoneUtil = PhoneNumberUtil::getInstance();
            $iso31662CountryCode = $phoneUtil->getRegionCodeForCountryCode((int)$value);

            Assert::lazy()->tryAll()
                ->that($value)
                ->nullOr()
                ->regex($phonePrefixPattern, 'Value must be a phone prefix', 'phonePrefix.invalidFormat')
                ->that($iso31662CountryCode)
                ->nullOr()
                ->notEq('ZZ', 'Value must be a phone prefix', 'phonePrefix.invalidValue')
                ->verifyNow();
        }

        return new static($value ? $value : null);
    }

    /**
     * @example: PhonePrefix::fromPrefix('GB')
     */
    public static function fromIso31662(?string $iso31662): static
    {
        if ($iso31662) {
            $phoneUtil = PhoneNumberUtil::getInstance();

            $value = $phoneUtil->getCountryCodeForRegion($iso31662);

            Assert::lazy()->tryAll()
                ->that($value)
                ->nullOr()
                ->notEq(0, 'Value must be a valid Iso-3166-2 code', 'phonePrefix.invalidIsoValue')
                ->verifyNow();

            return new static((string)$value);
        }

        return new static(null);
    }

    public function getIso31662(): string
    {
        $phoneUtil = PhoneNumberUtil::getInstance();
        return $phoneUtil->getRegionCodeForCountryCode($this->value);
    }

    /**
     * @deprecated use toString()
     */
    public function toPrimitive(): string|null
    {
        return $this->toString();
    }

    public function toString(): string|null
    {
        return $this->value;
    }
}
