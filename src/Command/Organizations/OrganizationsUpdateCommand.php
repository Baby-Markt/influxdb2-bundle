<?php

namespace Babymarkt\Symfony\Influxdb2Bundle\Command\Organizations;

use InfluxDB2\ApiException;
use InfluxDB2\Model\PatchOrganizationRequest;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'babymarkt-influxdb2:orgs:update',
    description: 'Update a existing organization'
)]
class OrganizationsUpdateCommand extends OrganizationsCreateCommand
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

        $orgId = $this->askForOrganizationId($io, $input->getArgument('org'));

        try {
            $organization = $this->service->getOrgsID($orgId);

        } catch (ApiException $e) {
            $io->error($e->getResponseBody());
            return 2;
        }

        $patchOrganizationRequest = (new PatchOrganizationRequest())
            ->setName($this->askForOrganizationName(
                io: $io,
                defaultName: $input->getOption('name') ?? $organization->getName()
            ))
            ->setDescription($this->askForDescription(
                io: $io,
                defaultDescription: $input->getOption('description') ?? $organization->getDescription()
            ));

        try {
            $organization = $this->service->patchOrgsID($orgId, $patchOrganizationRequest);
            $io->success(sprintf('Organization "%s" successfully updated!', $organization->getName()));
            $this->writeOrganizationTable($io, $organization);
            return 0;

        } catch (ApiException $e) {
            $io->error($e->getResponseBody());
            return 1;
        }
    }
}
