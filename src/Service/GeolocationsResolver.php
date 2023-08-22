<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Distances;
use App\DTO\Location;
use App\DTO\Locations;

interface GeolocationsResolver
{
    public function getDistancesBetweenGeolocations(Location $sourceLocation, Locations $destinationLocations): Distances;
}
