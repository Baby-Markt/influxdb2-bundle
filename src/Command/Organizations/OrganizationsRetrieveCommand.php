<?php

namespace Babymarkt\Symfony\Influxdb2Bundle\Command\Organizations;

use Babymarkt\Symfony\Influxdb2Bundle\Command\Organizations\AbstractOrganizationsCommand;
use InfluxDB2\ApiException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'babymarkt-influxdb2:orgs:retrieve',
    description: 'Retrieve a existing organization.'
)]
class OrganizationsRetrieveCommand extends AbstractOrganizationsCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        parent::configure();
        $this->addArgument(
            name: 'org',
            mode: InputArgument::OPTIONAL,
            description: 'The organization name or ID.'
        );
    }

    /**
     * @inheritDoc
     */
    protected function executeCommand(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $organizationId = $this->askForOrganizationId($io, $input->getArgument('org'));

        try {
            $this->writeOrganizationTable($io, $this->service->getOrgsID($organizationId));
            return 0;

        } catch (ApiException $e) {
            $io->error($e->getResponseBody());
            return 1;
        }
    }


}
