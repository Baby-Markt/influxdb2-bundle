<?php
declare(strict_types=1);

namespace Babymarkt\Symfony\Influxdb2Bundle\Command\Organizations;

use Babymarkt\Symfony\Influxdb2Bundle\Registry\ClientNotFoundException;
use Babymarkt\Symfony\Influxdb2Bundle\Registry\ClientRegistry;
use InfluxDB2\Client;
use InfluxDB2\Model\Organization;
use InfluxDB2\Service\OrganizationsService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractOrganizationsCommand extends Command
{
    protected Client $client;
    protected OrganizationsService $service;

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
            $this->client = $this->registry->getClient($input->getOption('client'));
        } catch (ClientNotFoundException $e) {
            $io->error($e->getMessage());
            return 3;
        }

        /** @var OrganizationsService $orgService */
        /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $this->service = $this->client->createService(OrganizationsService::class);

        return $this->executeCommand($input, $output);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected abstract function executeCommand(InputInterface $input, OutputInterface $output): int;


    /**
     * @param SymfonyStyle $io
     * @param string|null $defaultOrg
     * @return string
     */
    protected function askForOrganizationId(SymfonyStyle $io, ?string $defaultOrg): string
    {
        $choices = [];

        foreach ($this->service->getOrgs()->getOrgs() as $org) {
            if ($org->getId() === $defaultOrg) {
                $defaultOrg = $org->getName();
            }
            $choices[$org->getId()] = $org->getName();
        }

        // Returns the key of the selected organization.
        return (string)$io->choice('Organization', $choices, $defaultOrg);
    }

    /**
     * @param SymfonyStyle $io
     * @param Organization $organization
     * @return void
     */
    protected function writeOrganizationTable(SymfonyStyle $io, Organization $organization): void
    {
        $table = $io->createTable();
        $table->setHeaderTitle('Organization ' . $organization->getName());
        $table->setHeaders(['Property', 'Value']);

        foreach (Organization::getters() as $property => $getter) {
            $value = match ($property) {
                'created_at', 'updated_at' => $organization->$getter()->format('Y-m-d H:i:s'),
                default => (string)$organization->$getter(),
            };

            $table->addRow([$property, $value]);
        }

        $table->render();
    }
}
