<?php

declare(strict_types=1);

namespace App\Tests\ValueObject;

use App\ValueObject\Coordinates;
use PHPUnit\Framework\TestCase;

final class CoordinatesTest extends TestCase
{
    public function test_it_throws_an_exception_when_incorrect_latitude_is_provided(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Latitude must be between/');

        new Coordinates(180, 180);
    }

    public function test_it_throws_an_exception_when_incorrect_longitude_is_provided(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Longitude must be between/');

        new Coordinates(90, 190);
    }
}
