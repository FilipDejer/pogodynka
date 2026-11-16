<?php

namespace App\Command;

use App\Service\WeatherUtil;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'weather:city',
    description: 'Fetches weather for a country code and city name',
)]
class WeatherCityCommand extends Command
{
    public function __construct(
        private WeatherUtil $weatherUtil
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('countryCode', InputArgument::REQUIRED, 'Country code (e.g., PL)')
            ->addArgument('cityName', InputArgument::REQUIRED, 'City name (e.g., Szczecin)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $countryCode = $input->getArgument('countryCode');
        $cityName = $input->getArgument('cityName');

        $measurements = $this->weatherUtil->getWeatherForCountryAndCity($countryCode, $cityName);

        if (empty($measurements)) {
            $io->error(sprintf('No measurements found for %s, %s.', $cityName, $countryCode));
            return Command::FAILURE;
        }

        $io->writeln(sprintf('Location: %s (%s)', $cityName, $countryCode));
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
