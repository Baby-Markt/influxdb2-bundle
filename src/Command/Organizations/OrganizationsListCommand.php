<?php
declare(strict_types=1);

namespace Babymarkt\Symfony\Influxdb2Bundle\Command\Organizations;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'babymarkt-influxdb2:orgs:list',
    description: 'List all existing organizations.'
)]
class OrganizationsListCommand extends AbstractOrganizationsCommand
{
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
    protected function executeCommand(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $table = $io->createTable()
            ->setHeaderTitle('All organizations')
            ->setHeaders(['Name', 'Id', 'Description', 'Created-At', 'Updated-At']);

        foreach ($this->service->getOrgs()->getOrgs() as $org) {
            $table->addRow([
                $org->getName(),
                $org->getId(),
                $org->getDescription(),
                $org->getCreatedAt()->format('Y-m-d H:i:s'),
                $org->getUpdatedAt()->format('Y-m-d H:i:s')
            ]);
        }

        $table->render();

        return 0;
    }

}
