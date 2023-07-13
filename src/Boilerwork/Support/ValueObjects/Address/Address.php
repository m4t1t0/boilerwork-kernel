<?php

declare(strict_types=1);

namespace Boilerwork\Support\ValueObjects\Address;

use Boilerwork\Foundation\ValueObjects\ValueObject;
use Boilerwork\Support\ValueObjects\Country\Country;
use Boilerwork\Support\ValueObjects\Country\Iso31661Alpha2Code;
use Boilerwork\Support\ValueObjects\Geo\Coordinates;

/**
 * @deprecated
 */
final class Address extends ValueObject
{
    private function __construct(
        private Street $street,
        private ?AdministrativeArea $administrativeArea1,
        private ?AdministrativeArea $administrativeArea2,
        private ?PostalCode $postalCode,
        private Location $location,
        private Country $country,
        private ?Coordinates $coordinates,
    ) {
    }

    public static function fromScalars(
        string $streetName,
        ?string $streetNumber,
        ?string $streetOther1,
        ?string $streetOther2,
        ?string $administrativeArea1,
        ?string $administrativeArea2,
        ?string $postalCode,
        string $location,
        string $countryIso31662,
        ?float $latitude,
        ?float $longitude,
    ): self {
        $coordinates = ($latitude !== null && $longitude !== null) ? Coordinates::fromValues($latitude, $longitude) : null;

        return new self(
            street: Street::fromValues($streetName, $streetNumber, $streetOther1, $streetOther2),
            administrativeArea1: $administrativeArea1 ? AdministrativeArea::fromString($administrativeArea1) : null,
            administrativeArea2: $administrativeArea2 ? AdministrativeArea::fromString($administrativeArea2) : null,
            postalCode: $postalCode ? PostalCode::fromString($postalCode, Iso31661Alpha2Code::fromString($countryIso31662)) : null,
            location: Location::fromId($location),
            country: Country::fromIso31661Alpha2Code(Iso31661Alpha2Code::fromString($countryIso31662)),
            coordinates: $coordinates,
        );
    }

    public function street(): Street
    {
        return $this->street;
    }

    public function administrativeArea1(): ?AdministrativeArea
    {
        return $this->administrativeArea1;
    }

    public function administrativeArea2(): ?AdministrativeArea
    {
        return $this->administrativeArea2;
    }

    public function postalCode(): ?PostalCode
    {
        return $this->postalCode;
    }

    public function location(): Location
    {
        return $this->location;
    }

    public function country(): Country
    {
        return $this->country;
    }

    public function coordinates(): ?Coordinates
    {
        return $this->coordinates;
    }

    public function hasCoordinates(): bool
    {
        return $this->coordinates() !== null;
    }

    public function toArray(): array
    {
        return [
            'street' => $this->street->toArray(),
            'administrativeArea1' => $this->administrativeArea1 ? $this->administrativeArea1->toString() : null,
            'administrativeArea2' => $this->administrativeArea2 ? $this->administrativeArea2->toString() : null,
            'postalCode' => $this->postalCode ? $this->postalCode->toString() : null,
            'location' => $this->location->toString(),
            'country' => $this->country->toString(),
            'coordinates' => $this->coordinates->toArray(),
        ];
    }
}
