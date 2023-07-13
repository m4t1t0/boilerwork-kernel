#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace Boilerwork\Support\ValueObjects;

use Boilerwork\Foundation\ValueObjects\ValueObject;
use Boilerwork\Validation\Assert;

/**
 * 
 **/
abstract class PersonName extends ValueObject
{
    private string $latinUnicodePattern = '/^([a-zA-ZÀ-ÖÙ-öù-ÿĀ-žḀ-ỿ\s\-\']+)$/';

    private string $latinUnicodeOrEmptyStringPattern = '/^(^$|[a-zA-ZÀ-ÖÙ-öù-ÿĀ-žḀ-ỿ\s\-\']+)$/';

    public function __construct(
        public readonly string $firstName,
        public readonly string $middleName,
        public readonly string $lastName,
        public readonly string $lastName2,
    ) {
        Assert::lazy()->tryAll()
            ->that($firstName)
            ->regex($this->latinUnicodePattern, 'Only latin unicode letters and - \' allowed', 'personNameFirstName.invalidValue')
            ->maxLength(32, 'Maximum 32 characters length', 'personNameFirstName.maxLength')
            ->that($lastName)
            ->regex($this->latinUnicodePattern, 'Only latin unicode letters and - \' allowed', 'personNameLastName.invalidValue')
            ->maxLength(32, 'Maximum 32 characters length', 'personNameLastName.maxLength')
            ->that($middleName)
            ->regex($this->latinUnicodeOrEmptyStringPattern, 'Only latin unicode letters and - \' allowed or empty string', 'personNameMiddleName.invalidValue')
            ->maxLength(32, 'Maximum 32 characters length', 'personNameMiddleName.maxLength')
            ->that($lastName2)
            ->regex($this->latinUnicodeOrEmptyStringPattern, 'Only latin unicode letters and - \' allowed or empty string', 'personNameLastName2.invalidValue')
            ->maxLength(32, 'Maximum 32 characters length', 'personNameLastName2.maxLength')
            ->verifyNow();
    }

    /**
     * Name string is returned in the following format and order:
     * (Given names + Inherited names)
     * firstName middleName lastName lastName2
     *
     * @return string
     */
    public function toString(): string
    {
        return sprintf('%s %s %s %s', $this->firstName, $this->middleName, $this->lastName, $this->lastName2);
    }

    /**
     * @deprecated use toString()
     */
    public function toPrimitive(): string
    {
        return $this->toString();
    }

    /**
     * First name of a person.
     */
    public function firstName(): string
    {
        return $this->firstName;
    }

    /**
     * Middle name
     * Typical in English speaking countries.
     */
    public function middleName(): string
    {
        return $this->middleName;
    }

    /**
     * Last name of a person.
     */
    public function lastName(): string
    {
        return $this->lastName;
    }

    /**
     * Second Last name of a person.
     * (Segundo Apellido: In Spain and other Spanish speaking countries, as well as in Portugal)
     */
    public function lastName2(): string
    {
        return $this->lastName2;
    }

    public function toArray(): array
    {
        return [
            'firstName' => $this->firstName(),
            'middleName' => $this->middleName(),
            'lastName' => $this->lastName(),
            'lastName2' => $this->lastName2(),
        ];
    }
}
