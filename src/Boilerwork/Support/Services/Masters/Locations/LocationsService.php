#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace Boilerwork\Support\Services\Masters\Locations;

use Boilerwork\Persistence\Adapters\ElasticSearch\ElasticSearchAdapter;
use Boilerwork\Support\ValueObjects\Geo\Coordinates;
use Boilerwork\Support\ValueObjects\Geo\Country\Iso31661Alpha2;
use Boilerwork\Support\ValueObjects\Geo\Location;
use Boilerwork\Support\ValueObjects\Identity;

use function count;
use function trim;

final class LocationsService implements LocationsInterface
{
    public const LOCATIONS_INDEX = 'all_locations';

    public function __construct(
        private readonly ElasticSearchAdapter $client,
    ) {
    }

    public function getLocationById(string $id): LocationEntity|LocationEntityNotFound
    {
        $params = [
            'index' => self::LOCATIONS_INDEX,
            'body'  => [
                'query' => [
                    'term' => [
                        'id' => trim($id),
                    ],
                ],
            ],
        ];

        $response = $this->client->search($params);

        return $this->buildLocationEntity($response['hits']['hits']);
    }

    public function searchSimilarLocation(
        string $locationName,
        Coordinates $coordinates,
        ?Iso31661Alpha2 $iso3166Alpha2,
    ) {
        $params = [
            'index' => self::LOCATIONS_INDEX,
            'body'  => [
                'query' => [
                    'function_score' => [
                        'query'      => [
                            'bool' => [
                                'must'   => [
                                    [
                                        'multi_match' => [
                                            'query'                => $locationName,
                                            'fuzziness'            => 'AUTO',
                                            'fields'               => [
                                                'location_es',
                                                'location_en',
                                            ],
                                            'type'                 => 'best_fields',
                                            'minimum_should_match' => '2<2', // Two words coincidence has priority
                                        ],
                                    ],
                                ],
                                'filter' => [
                                    [
                                        'geo_distance' => [
                                            'distance'    => '40km',
                                            'coordinates' => [
                                                'lat' => $coordinates->latitude(),
                                                'lon' => $coordinates->longitude(),
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'functions'  => [
                            [
                                'exp'    => [
                                    'coordinates' => [
                                        'origin' => [
                                            'lat' => $coordinates->latitude(),
                                            'lon' => $coordinates->longitude(),
                                        ],
                                        'scale'  => '20km',
                                        'offset' => '20km',
                                        'decay'  => 0.5,
                                    ],
                                ],
                                'weight' => 2,
                            ],
                        ],
                        'score_mode' => 'sum',
                        'boost_mode' => 'multiply',
                    ],
                ],
                'sort'  => [
                    [
                        '_geo_distance' => [
                            'coordinates' => [
                                'lat' => $coordinates->latitude(),
                                'lon' => $coordinates->longitude(),
                            ],
                            'order'       => 'asc',
                            'unit'        => 'km',
                        ],
                    ],
                    [
                        '_score' => [
                            'order' => 'desc',
                        ],
                    ],
                ],
            ],
            'size'  => 1,

        ];

        if ($iso3166Alpha2) {
            $params['body']['query']['function_score']['query']['bool']['must'][] = [
                'match' => [
                    'iso_alpha_2' => [
                        'query' => $iso3166Alpha2->toString(),
                        'boost' => 3,
                    ],
                ],
            ];
        }


        $response = $this->client->search($params);

        return $this->buildLocationEntity($response['hits']['hits']);
    }

    private function buildLocationEntity($hits): LocationEntity
    {
        if (count($hits) > 0) {
            // Retrieve first result, assuming it is the best result
            $hit = $hits[0]['_source'];

            return new LocationEntity(
                id: Identity::fromString($hit['id']),
                location: Location::fromScalars(
                    name: [
                        'ES' => $hit['location_es'],
                        'EN' => $hit['location_en'],
                    ],
                    iso31661Alpha2: $hit['iso_alpha_2'],
                    latitude: $hit['coordinates']['lat'],
                    longitude: $hit['coordinates']['lon'],
                )
            );
        }

        return new LocationEntityNotFound();
    }
}
