<?php

declare(strict_types=1);

namespace App\Command;

use App\DTO\Distance;
use App\DTO\Location;
use App\DTO\Locations;
use App\Service\ExporterInterface;
use App\Service\GeolocationsResolver;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\TaggedLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Webmozart\Assert\Assert;
use Webmozart\Assert\InvalidArgumentException;

#[AsCommand('app:get-distances')]
final class ResolveGeolocationsCommand extends Command
{
    public const SOURCE_LOCATION = 'Adchieve HQ - Sint Janssingel 92, 5211 DAs-Hertogenbosch, The Netherlands';
    public const DESTINATION_LOCATIONS = [
        'Deldenerstraat 70, 7551AH Hengelo, The Netherlands',
        'Dada House , Inside dada silk mills compound, Udhana Main Rd, near Chhaydo Hospital, Surat, 394210, India',
        'Eastern Enterprise LLC, Building No. 36B, Rehana Plaza Zahraa El Maadi St., Cairo, Egypt',
        '505, 3013 AL Rotterdam, The Netherlands',
        'Sherlock Holmes - 221B Baker St., London, United Kingdom',
        'The White House, 1600, Pennsylvania Avenue Northwest, Washington, District of Columbia, 20006, USA',
        'The Empire State Building - 350 Fifth Avenue, New York City, NY 10118',
        'Saint Martha House, 00120 Citta del Vaticano, Vatican City',
        'Neverland - 5225 Figueroa Mountain Road, Los Olivos, Calif. 93441, USA',
    ];

    public function __construct(
        #[TaggedLocator(tag: ExporterInterface::class, defaultIndexMethod: 'format')]
        private readonly ServiceLocator $taggedLocator,
        private readonly GeolocationsResolver $geolocationsResolver,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp(
                <<<STRING
                    This command gets the distances between various location addresses, writes them to a csv file and
                    dumps them in the console.
                STRING
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->info('Retrieving the distances between the geolocations!');

        $destinationLocations = new Locations(
            locations: \array_map(static fn(string $location) => new Location($location), self::DESTINATION_LOCATIONS),
        );
        $distances = $this->geolocationsResolver->getDistancesBetweenGeolocations(
            sourceLocation: new Location(self::SOURCE_LOCATION),
            destinationLocations: $destinationLocations,
        );

        $io->info('Generating the csv!');

        try {
            $exporter = $this->taggedLocator->get(ExporterInterface::EXPORT_CSV);

            Assert::isInstanceOf($exporter, ExporterInterface::class);

            $errorInWritingToTheFile = $exporter->export($distances);
        } catch (NotFoundExceptionInterface|ContainerExceptionInterface|InvalidArgumentException) {
            $errorInWritingToTheFile = true;
        }

        if (
            $errorInWritingToTheFile || 0 === \count($distances->distancesBetweenSourceAndDestinationLocations)
        ) {
            $io->error('There was an error in generating the csv file');

            return Command::FAILURE;
        }

        $io->success('A csv file containing the distances was successfully generated in the public directory');

        $io->title('The distances between the geolocations are printed below:');

        $io->table(
            ['Sort number', 'Distance', 'Address'],
            $this->prepareDistancesForTheConsole($distances->distancesBetweenSourceAndDestinationLocations)
        );

        return Command::SUCCESS;
    }

    /**
     * @param Distance[] $distances
     *
     * @return list<list<int, string, string>>
     */
    private function prepareDistancesForTheConsole(array $distances): array
    {
        return \array_map(
            static fn(Distance $distance) => [$distance->sortNumber, $distance->distance, $distance->address],
            $distances,
        );
    }
}
