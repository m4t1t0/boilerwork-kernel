#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace Boilerwork\Support\ValueObjects\Country;

use Boilerwork\Foundation\ValueObjects\ValueObject;

/**
 *  Creates UUID using Symfony\Polyfill implementation, which turns out to be faster than pecl extension.
 **/
final class Country extends ValueObject
{
    public function __construct(
        protected Iso31661Alpha2Code $iso31661Alpha2Code,
        protected Iso31661Alpha3Code $iso31661Alpha3Code,
        protected string $englishName,
    ) {
    }

    public static function fromIso31661Alpha2Code(Iso31661Alpha2Code $code): static
    {
        $data = DataProvider::fromIso31661Alpha2Code($code);

        $alpha2      = Iso31661Alpha2Code::fromString($data[0]);
        $alpha3      = Iso31661Alpha3Code::fromString($data[1]);

        $name        = $data[3];

        return new static($alpha2, $alpha3, $name);
    }

    public static function fromIso31661Alpha3Code(Iso31661Alpha3Code $code): static
    {
        $data = DataProvider::fromIso31661Alpha3Code($code);

        $alpha2      = Iso31661Alpha2Code::fromString($data[0]);
        $alpha3      = Iso31661Alpha3Code::fromString($data[1]);
        $name        = $data[3];

        return new static($alpha2, $alpha3, $name);
    }

    public function iso31661Alpha2Code(): Iso31661Alpha2Code
    {
        return $this->iso31661Alpha2Code;
    }

    public function iso31661Alpha3Code(): Iso31661Alpha3Code
    {
        return $this->iso31661Alpha3Code;
    }

    public function englishName(): string
    {
        return $this->englishName;
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
        return $this->iso31661Alpha2Code()->toString();
    }

    public function equals(ValueObject $object): bool
    {
        return $this->iso31661Alpha2Code->toString() === $object->iso31661Alpha2Code->toString()
            && $this->iso31661Alpha3Code->toString() === $object->iso31661Alpha3Code->toString()
            && $this->englishName === $object->englishName
            && $object instanceof self;
    }

    public function toArray(): array
    {
        return [
            'iso31661Alpha2Code' => $this->iso31661Alpha2Code()->toString(),
            'iso31661Alpha3Code' => $this->iso31661Alpha3Code()->toString(),
            'englishName' => $this->englishName(),
        ];
    }
}
