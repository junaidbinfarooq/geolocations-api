<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Distances;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Serializer\SerializerInterface;

#[AutoconfigureTag]
final class CsvExporter implements ExporterInterface
{
    public const FILE_NAME = 'distances.csv';

    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly FilesystemOperator  $defaultFilesystem,
    )
    {
    }

    public function export(Distances $distances): bool
    {
        $csvString = $this->serializer->serialize($distances->distancesBetweenSourceAndDestinationLocations, self::format());
        $errorInWritingToTheFile = false;

        try {
            $this->defaultFilesystem->write(self::FILE_NAME, $csvString);
        } catch (FilesystemException) {
            $errorInWritingToTheFile = true;
        }

        return $errorInWritingToTheFile;
    }

    public static function format(): string
    {
        return ExporterInterface::EXPORT_CSV;
    }
}
