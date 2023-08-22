<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Distance;
use App\DTO\Distances;
use App\DTO\Location;
use App\DTO\Locations;
use App\ValueObject\Coordinates;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class GeolocationsResolverUsingPositionStackApi implements GeolocationsResolver
{
    public const BASE_URI = 'http://api.positionstack.com/v1';

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly CacheInterface      $cache,
        private readonly LoggerInterface $logger,
        private readonly string              $apiKey,
    )
    {
    }

    public function getDistancesBetweenGeolocations(Location $sourceLocation, Locations $destinationLocations): Distances
    {
        $distances = new Distances();

        $sourceCoordinates = $this->getCoordinatesForTheLocation($sourceLocation);

        if (null === $sourceCoordinates) {
            return $distances;
        }

        $distancesBetweenSourceAndDestinationLocations = [];

        foreach ($destinationLocations->locations as $destinationLocation) {
            $coordinatesForTheLocation = $this->getCoordinatesForTheLocation($destinationLocation);

            if (null === $coordinatesForTheLocation) {
                continue;
            }

            $distanceBetweenSourceAndDestinationLocation = $this->getDistanceBetweenLocations($sourceCoordinates, $coordinatesForTheLocation);

            if (
                null === $distanceBetweenSourceAndDestinationLocation
            ) {
                continue;
            }

            $distancesBetweenSourceAndDestinationLocations[$destinationLocation->name] = $distanceBetweenSourceAndDestinationLocation;
        }

        \asort($distancesBetweenSourceAndDestinationLocations, SORT_NUMERIC);

        $index = 1;

        foreach ($distancesBetweenSourceAndDestinationLocations as $location => $distance) {
            $distances->distancesBetweenSourceAndDestinationLocations[] = new Distance(
                sortNumber: $index,
                distance: \number_format($distance, 2) . ' Km',
                address: $location,
            );

            $index++;
        }

        return $distances;
    }

    private function getCoordinatesForTheLocation(Location $location): ?Coordinates
    {
        try {
            return $this->cache->get(
                'search_' . \md5($location->name),
                function () use ($location) {
                    $response = $this->client->request(
                        'GET',
                        \sprintf('%s/%s', self::BASE_URI, 'forward'),
                        [
                            'query' => [
                                'access_key' => $this->apiKey,
                                'query' => $location->name,
                                'limit' => 1,
                            ]
                        ],
                    );

                    $statusCode = $response->getStatusCode();
                    $content = $response->toArray();
                    $latitude = $content['data'][0]['latitude'] ?? null;
                    $longitude = $content['data'][0]['longitude'] ?? null;

                    if (
                        Response::HTTP_OK !== $statusCode
                        || null === $latitude
                        || null === $longitude
                    ) {
                        throw new \DomainException(
                            \sprintf("Coordinates for the location '%s' could not be fetched", $location->name),
                        );
                    }

                    return new Coordinates($latitude, $longitude);
                }
            );
        } catch (InvalidArgumentException|ExceptionInterface|\DomainException $e) {
            $this->logger->error(
                'There was an error while getting the coordinates for a location',
                [
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                ]
            );

            return null;
        }
    }

    private function getDistanceBetweenLocations(Coordinates $sourceCoordinates, Coordinates $destinationCoordinates): ?float
    {
        try {
            return $this->cache->get(
                'distance_' . \md5("$sourceCoordinates:$destinationCoordinates"),
                function () use ($sourceCoordinates, $destinationCoordinates) {
                    $sourceLatitude = $sourceCoordinates->latitude();
                    $destinationLatitude = $destinationCoordinates->latitude();
                    $sourceLongitude = $sourceCoordinates->longitude();
                    $destinationLongitude = $destinationCoordinates->longitude();

                    if ($sourceLatitude === $destinationLatitude && $sourceLongitude === $destinationLongitude) {
                        return 0.0;
                    }

                    $distance =
                        \sin(\deg2rad($sourceLatitude))
                        * \sin(\deg2rad($destinationLatitude))
                        + \cos(\deg2rad($sourceLatitude))
                        * \cos(\deg2rad($destinationLatitude))
                        * \cos(\deg2rad($sourceLongitude - $destinationLongitude));
                    $distance = \acos($distance);
                    $distance = \rad2deg($distance);

                    return $distance * 60 * 1.1515 * 1.609344;
                }
            );
        } catch (InvalidArgumentException $e) {
            $this->logger->error(
                'The distance between a pair of coordinates could not be calculated',
                [
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                ]
            );

            return null;
        }
    }
}
