<?php

namespace App\Tests\Service;

use App\Command\ResolveGeolocationsCommand;
use App\DTO\Distance;
use App\DTO\Distances;
use App\DTO\Location;
use App\DTO\Locations;
use App\Service\GeolocationsResolverUsingPositionStackApi;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;

final class GeolocationsResolverUsingPositionStackApiTest extends TestCase
{
    public function test_it_successfully_retrieves_the_correct_distances(): void
    {
        // Arrange
        $responses = $this->getMockResponses([
            [51.6882, 5.298532],
            [52.26633, 6.78576],
        ]);
        $api = new GeolocationsResolverUsingPositionStackApi(
            new MockHttpClient($responses),
            new ArrayAdapter(),
            $this->createMock(LoggerInterface::class),
            ''
        );

        $expectedDistances = new Distances([
            new Distance(1, '0.00 Km', ResolveGeolocationsCommand::SOURCE_LOCATION),
            new Distance(2, '120.44 Km', ResolveGeolocationsCommand::DESTINATION_LOCATIONS[0]),
        ]);

        // Act
        $actualDistances = $api->getDistancesBetweenGeolocations(
            new Location(ResolveGeolocationsCommand::SOURCE_LOCATION),
            new Locations([
                new Location(ResolveGeolocationsCommand::SOURCE_LOCATION),
                new Location(ResolveGeolocationsCommand::DESTINATION_LOCATIONS[0]),
            ]),
        );

        // Assert
        self::assertCount(2, $actualDistances->distancesBetweenSourceAndDestinationLocations);
        self::assertObjectEquals($expectedDistances, $actualDistances);
    }

    /**
     * @dataProvider provideLocationData
     */
    public function test_it_does_not_fetch_any_distance_if_incorrect_source_or_destination_location_is_provided(
        array     $responses,
        Location  $sourceLocation,
        Locations $destinationLocations,
        Distances $expectedDistances,
    ): void
    {
        $api = new GeolocationsResolverUsingPositionStackApi(
            new MockHttpClient($responses),
            new ArrayAdapter(),
            $this->createMock(LoggerInterface::class),
            ''
        );

        $actualDistances = $api->getDistancesBetweenGeolocations($sourceLocation, $destinationLocations);

        self::assertObjectEquals($expectedDistances, $actualDistances);
    }

    /**
     * @return \Generator<list<list<JsonMockResponse>, Location, Locations, Distances>>
     */
    public function provideLocationData(): \Generator
    {
        yield 'incorrect source location' => [
            $this->getMockResponses([[null, null], [52.26633, 6.78576]]),
            new Location(''),
            new Locations([
                new Location(ResolveGeolocationsCommand::SOURCE_LOCATION),
                new Location(ResolveGeolocationsCommand::DESTINATION_LOCATIONS[0]),
            ]),
            new Distances([]),
        ];

        yield 'incorrect destination location' => [
            $this->getMockResponses([[52.26633, 6.78576], [null, null]]),
            new Location(ResolveGeolocationsCommand::SOURCE_LOCATION),
            new Locations([
                new Location(''),
            ]),
            new Distances([]),
        ];

        yield 'empty locations' => [
            $this->getMockResponses([[null, null], [null, null]]),
            new Location(''),
            new Locations([
                new Location(''),
            ]),
            new Distances([]),
        ];
    }

    /**
     * @param list<list<float, float>> $coordinates
     *
     * @return JsonMockResponse[]
     */
    private function getMockResponses(array $coordinates): array
    {
        return \array_map(
            static fn(array $coordinatePair) => new JsonMockResponse([
                'data' => [['latitude' => $coordinatePair[0], 'longitude' => $coordinatePair[1]]],
            ]),
            $coordinates,
        );
    }
}
