<?php

declare(strict_types=1);

namespace App\DTO;

final class Location
{
    public function __construct(
        public string $name,
    )
    {
    }
}
