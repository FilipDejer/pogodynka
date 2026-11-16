<?php

namespace App\Command;

use App\Repository\LocationRepository;
use App\Service\WeatherUtil;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'weather:location',
    description: 'Fetches weather for a location ID',
)]
class WeatherLocationCommand extends Command
{
    public function __construct(
        private LocationRepository $locationRepository,
        private WeatherUtil $weatherUtil
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('id', InputArgument::REQUIRED, 'Location ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $id = $input->getArgument('id');

        $location = $this->locationRepository->find($id);

        if (!$location) {
            $io->error(sprintf('Location with ID %s not found.', $id));
            return Command::FAILURE;
        }

        $measurements = $this->weatherUtil->getWeatherForLocation($location);

        $io->writeln(sprintf('Location: %s', $location->getCity()));
        $io->table(
            ['Date', 'Temperature', 'Humidity', 'Wind Speed', 'Cloudiness'],
            array_map(fn($m) => [
                $m->getDate() ? $m->getDate()->format('Y-m-d') : '',
                $m->getCelcius(),
                $m->getHumidity(),
                $m->getWindSpeed(),
                $m->getCloudiness(),
            ], $measurements)
        );

        return Command::SUCCESS;
    }
}
