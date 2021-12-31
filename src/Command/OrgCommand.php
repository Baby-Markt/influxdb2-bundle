<?php

namespace Babymarkt\Symfony\Influxdb2Bundle\Command;

use Babymarkt\Symfony\Influxdb2Bundle\InfluxDb\ClientRegistry;
use InfluxDB2\Service\OrganizationsService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'babymarkt-influxdb2:org',
    description: 'Let you create a new or update an existing org.'
)]
class OrgCommand extends Command
{
    public function __construct(protected ClientRegistry $registry)
    {
        parent::__construct();
    }

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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $clientName = $input->getOption('client');
        if (!$this->registry->hasClient($clientName)) {
            throw new \InvalidArgumentException(sprintf('Client "%s" not found.', $clientName));
        }

        $client = $this->registry->getClient($clientName);

        $io = new SymfonyStyle($input, $output);
        $io->writeln('Not implemented yet!');

        /** @var OrganizationsService $orgService */
        $orgService = $client->createService(OrganizationsService::class);

        $table = $io->createTable();
        $table->setHeaderTitle('All organisations')
            ->setHeaders(['Name', 'Id', 'Created-At', 'Description']);
        foreach ($orgService->getOrgs()->getOrgs() as $org) {
            $table->addRow([
                $org->getName(),
                $org->getId(),
                $org->getCreatedAt(),
                $org->getDescription()
            ]);
        }

        $table->render();
    }


}
