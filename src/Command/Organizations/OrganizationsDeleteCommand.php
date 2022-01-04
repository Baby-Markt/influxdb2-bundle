<?php
declare(strict_types=1);

namespace Babymarkt\Symfony\Influxdb2Bundle\Command\Organizations;

use InfluxDB2\ApiException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'babymarkt-influxdb2:orgs:delete',
    description: 'Delete a existing organization.'
)]
class OrganizationsDeleteCommand extends AbstractOrganizationsCommand
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
            $this->service->deleteOrgsID($organizationId);
            $io->success(sprintf('Organization with ID "%s" successfully deleted!', $organizationId));
            return 0;

        } catch (ApiException $e) {
            $io->error($e->getResponseBody());
            return 1;
        }
    }


}
