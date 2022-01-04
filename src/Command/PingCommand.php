<?php
declare(strict_types=1);

namespace Babymarkt\Symfony\Influxdb2Bundle\Command;

use Babymarkt\Symfony\Influxdb2Bundle\Registry\ClientNotFoundException;
use Babymarkt\Symfony\Influxdb2Bundle\Registry\ClientRegistry;
use InfluxDB2\Service\PingService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'babymarkt-influxdb2:ping',
    description: 'Checks the status and version of an InfluxDB instance.'
)]
class PingCommand extends Command
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
            return 3;
        }

        /** @var PingService $pingService */
        $pingService = $client->createService(PingService::class);

        $info = $pingService->getPingWithHttpInfo();
        $host = $client->getConfiguration()->getHost();

        if ($info[1] === 204) {
            $io->success(sprintf("PING %s: OK (%s %s)",
                $host,
                $info[2]["X-Influxdb-Build"][0],
                $info[2]["X-Influxdb-Version"][0],
            ));
        } else {
            $io->error(sprintf("PING %s: FAILED", $host));
        }

        return 0;
    }


}
