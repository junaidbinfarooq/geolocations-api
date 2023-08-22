<?php

declare(strict_types=1);

namespace App\DTO;

final class Distances
{
    public function __construct(
        /** @var Distance[] */
        public array $distancesBetweenSourceAndDestinationLocations = []
    )
    {
    }

    public function equals(Distances $distances): bool
    {
        $index = 0;

        foreach ($this->distancesBetweenSourceAndDestinationLocations as $distance) {
            if (
                null === ($distancesBetweenSourceAndDestinationLocations = $distances->distancesBetweenSourceAndDestinationLocations[$index] ?? null)
            ) {
                return false;
            }

            $isEqual = $distance->equals($distancesBetweenSourceAndDestinationLocations);

            if (!$isEqual) {
                return false;
            }

            $index++;
        }

        return true;
    }
}
