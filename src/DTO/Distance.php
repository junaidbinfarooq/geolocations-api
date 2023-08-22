<?php

declare(strict_types=1);

namespace App\DTO;

final class Distance
{
    public function __construct(
        public int    $sortNumber,
        public string $distance,
        public string $address,
    )
    {
    }

    public function equals(Distance $distance): bool
    {
        return
            $this->sortNumber === $distance->sortNumber &&
            $this->distance === $distance->distance &&
            $this->address === $distance->address;
    }
}
