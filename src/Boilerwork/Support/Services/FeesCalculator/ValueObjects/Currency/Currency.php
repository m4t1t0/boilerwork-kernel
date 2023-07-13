#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace Boilerwork\Support\Services\FeesCalculator\ValueObjects\Currency;

use Boilerwork\Foundation\ValueObjects\ValueObject;

final class Currency extends ValueObject
{
    public function __construct(
        protected Iso4217Code $iso4217,
        protected CurrencySymbol $symbol
    )
    {
    }

    public static function fromData(string $alpha3, string $symbol): static
    {
        $alpha3 = new Iso4217Code($alpha3);
        $symbol = new CurrencySymbol($symbol);

        return new static ($alpha3, $symbol);
    }

    public static function fromIsoCode(string $code): static
    {
        return self::fromIso4217Code(new Iso4217Code($code));
    }

    public static function fromIso4217Code(Iso4217Code $code): static
    {
        $data = DataProvider::fromIso4217Code($code);

        return self::fromData($data[0], $data[1]);
    }

    public function iso4217(): Iso4217Code
    {
        return $this->iso4217;
    }

    public function isoCode(): string
    {
        return $this->iso4217()->toPrimitive();
    }

    public function symbol(): CurrencySymbol
    {
        return $this->symbol;
    }

    /**
     * @deprecated use toString()
     */
    public function toPrimitive(): string
    {
        return $this->iso4217()->toString();
    }

    public function toString(): string
    {
        return $this->iso4217()->toString();
    }

    public function equals(Currency $object): bool
    {
        return $this->toPrimitive() === $object->iso4217()->toPrimitive()
            && $this->symbol->toPrimitive() === $object->symbol()->toPrimitive()
            && $object instanceof self;
    }

    public function toArray(): array
    {
        return [
            'iso3' => $this->isoCode(),
            'symbol' => $this->symbol()->toPrimitive()
        ];
    }

    public static function allValues(): array
    {
        return array_map(
            fn($data) => self::fromData($data[0], $data[1])
            , DataProvider::data()
        );
    }

    public function precision(): int
    {
        if (array_key_exists($this->isoCode(), self::$precision)) {
            return self::$precision[$this->isoCode()];
        }

        return 2;
    }

    private static array $precision = [
        'BHD' => 3,
        'BIF' => 0,
        'CLF' => 4,
        'CLP' => 0,
        'CVE' => 0,
        'DJF' => 0,
        'GNF' => 0,
        'IQD' => 3,
        'ISK' => 0,
        'JOD' => 3,
        'JPY' => 0,
        'KMF' => 0,
        'KRW' => 0,
        'KWD' => 3,
        'LYD' => 3,
        'OMR' => 3,
        'PYG' => 0,
        'RWF' => 0,
        'TND' => 3,
        'UGX' => 0,
        'VND' => 0,
        'VUV' => 0,
        'XAF' => 0,
        'XOF' => 0,
        'XPF' => 0,
    ];
}
