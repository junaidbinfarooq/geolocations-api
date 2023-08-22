<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Distances;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag]
interface ExporterInterface
{
    public const EXPORT_CSV = 'csv';

    public function export(Distances $distances): bool;

    public static function format(): string;
}
