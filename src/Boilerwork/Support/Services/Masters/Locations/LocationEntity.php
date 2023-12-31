#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace Boilerwork\Support\Services\Masters\Locations;

use Boilerwork\Support\ValueObjects\Geo\Location;
use Boilerwork\Support\ValueObjects\Identity;

readonly class LocationEntity
{
    public function __construct(
        public Identity $id,
        public Location $location
    ) {
    }

    public function toArray(?string $lang = null): array
    {
        return [
            'id' => $this->id->toString(),
            ...$this->location->toArray($lang)
        ];
    }
}