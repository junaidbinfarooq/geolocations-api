<?php

declare(strict_types=1);

namespace App\ValueObject;

final class Coordinates implements \Stringable
{
    private float $latitude;
    private float $longitude;

    public function __construct(float $latitude, float $longitude)
    {
        if ($latitude > 90 || $latitude < -90) {
            throw new \InvalidArgumentException(
                'Latitude must be between -90 and 90'
            );
        }

        if ($longitude > 180 || $longitude < -180) {
            throw new \InvalidArgumentException(
                'Longitude must be between -180 and 180'
            );
        }

        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }

    public function latitude(): float
    {
        return $this->latitude;
    }

    public function longitude(): float
    {
        return $this->longitude;
    }

    public function __toString(): string
    {
        return "{$this->latitude}_$this->longitude";
    }
}
