<?php
declare(strict_types=1);

namespace Babymarkt\Symfony\Influxdb2Bundle\Command;

use Babymarkt\Symfony\Influxdb2Bundle\Registry\ClientNotFoundException;
use Babymarkt\Symfony\Influxdb2Bundle\Registry\ClientRegistry;
use InfluxDB2\ApiException;
use InfluxDB2\Service\ReadyService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'babymarkt-influxdb2:ready',
    description: 'Get the readiness of an instance at startup.'
)]
class ReadyCommand extends Command
{
    public function __construct(protected ClientRegistry $registry)
    {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->addOption(
            name: 'client',
            shortcut: 'c',
            mode: InputOption::VALUE_OPTIONAL,
            description: 'The client to use.',
            default: 'default'
        );
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $client = $this->registry->getClient($input->getOption('client'));
        } catch (ClientNotFoundException $e) {
            $io->error($e->getMessage());
            return 1;
        }

        /** @var ReadyService $readyService */
        $readyService = $client->createService(ReadyService::class);

        try {
            $ready = $readyService->getReady();

            $io->success(sprintf("Status %s", $ready->getStatus()));
            $io->writeln(sprintf('Startet at %s', $ready->getStarted()->format('Y-m-d H:i:s')));
            $io->writeln(sprintf('Up since %s', $ready->getUp()));

        } catch (ApiException $e) {
            $io->error($e->getResponseBody());
            return 1;
        }

        return 0;
    }


}
