<?php

declare(strict_types=1);

namespace App\DTO;

final class Locations
{
    /**
     * @param Location[] $locations
     */
    public function __construct(
        public array $locations,
    )
    {
    }
}
