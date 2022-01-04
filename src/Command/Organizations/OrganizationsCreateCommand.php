<?php
declare(strict_types=1);

namespace Babymarkt\Symfony\Influxdb2Bundle\Command\Organizations;

use InfluxDB2\ApiException;
use InfluxDB2\Model\PostOrganizationRequest;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'babymarkt-influxdb2:orgs:create',
    description: 'Create a new organization'
)]
class OrganizationsCreateCommand extends AbstractOrganizationsCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        parent::configure();

        $this->addOption(
            name: 'name',
            mode: InputOption::VALUE_OPTIONAL,
            description: 'The organization name.'
        );

        $this->addOption(
            name: 'description',
            mode: InputOption::VALUE_OPTIONAL,
            description: 'The organization description.'
        );
    }


    /**
     * @inheritDoc
     */
    protected function executeCommand(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $postOrganizationRequest = (new PostOrganizationRequest())
            ->setName($this->askForOrganizationName($io, $input->getOption('name')))
            ->setDescription($this->askForDescription($io, $input->getOption('description')));

        try {
            $organization = $this->service->postOrgs($postOrganizationRequest);
            $io->success(sprintf('Organization "%s" successfully created!', $organization->getName()));
            $this->writeOrganizationTable($io, $organization);
            return 0;

        } catch (ApiException $e) {
            $io->error($e->getResponseBody());
            return 1;
        }
    }

    /**
     * @param SymfonyStyle $io
     * @param string|null $defaultName
     * @return string
     */
    protected function askForOrganizationName(SymfonyStyle $io, ?string $defaultName): string
    {
        return (string)$io->ask('Organization name', $defaultName, function ($v) {
            if (empty($v)) {
                throw new \RuntimeException('The organization name cannot be empty.');
            }
            return $v;
        });
    }

    /**
     * @param SymfonyStyle $io
     * @param string $defaultDescription
     * @return string
     */
    protected function askForDescription(SymfonyStyle $io, ?string $defaultDescription): string
    {
        return (string)$io->ask('Description', $defaultDescription);
    }


}
