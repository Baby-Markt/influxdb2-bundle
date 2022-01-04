<?php
declare(strict_types=1);

namespace Babymarkt\Symfony\Influxdb2Bundle\Command\Buckets;

use InfluxDB2\ApiException;
use InfluxDB2\Model\BucketRetentionRules;
use InfluxDB2\Model\PostBucketRequest;
use InfluxDB2\Model\SchemaType;
use InfluxDB2\Service\OrganizationsService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'babymarkt-influxdb2:buckets:create',
    description: 'Create a new bucket'
)]
class BucketsCreateCommand extends AbstractBucketsCommand
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
            description: 'The bucket name.'
        );

        $this->addOption(
            name: 'description',
            mode: InputOption::VALUE_OPTIONAL,
            description: 'The bucket description.'
        );

        $this->addOption(
            name: 'org',
            mode: InputOption::VALUE_OPTIONAL,
            description: 'The organization name or id.'
        );

        $this->addOption(
            name: 'duration',
            mode: InputOption::VALUE_OPTIONAL,
            description: 'The duration in seconds for how long data will be kept in the database. 0 means infinite.',
            default: 0
        );

        $this->addOption(
            name: 'schema-type',
            mode: InputOption::VALUE_OPTIONAL,
            description: 'The schema type. Allowed values are "implicit" or "explicit".',
            default: SchemaType::IMPLICIT
        );
    }


    /**
     * @inheritDoc
     */
    protected function executeCommand(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $postBucketRequest = (new PostBucketRequest())
            ->setName($this->askForBucketName($io, $input->getOption('name')))
            ->setOrgId($this->askForOrganizationId($io, $input->getOption('org')))
            ->setDescription($this->askForDescription($io, $input->getOption('description')))
            ->setRetentionRules($this->askForRetentionRules($io, (int)$input->getOption('duration')))
            ->setSchemaType($input->getOption('schema-type'));

        try {
            $bucket = $this->service->postBuckets($postBucketRequest);
            $io->success(sprintf('Bucket "%s" successfully created!', $bucket->getName()));
            $this->writeBucketTable($io, $bucket);
            return 0;

        } catch (ApiException $e) {
            $io->error($e->getResponseBody());
            return 1;
        }
    }

    /**
     * @param SymfonyStyle $io
     * @param int $defaultDuration
     * @return BucketRetentionRules[]
     */
    protected function askForRetentionRules(SymfonyStyle $io, int $defaultDuration): array
    {
        $duration = (int)$io->ask('Duration in seconds', (string)$defaultDuration, function ($v) {
            if ($v < 0) {
                throw new \RuntimeException('The duration must be >= 0.');
            }
            return $v;
        });

        if ($duration > 0) {
            $retentionRule = new BucketRetentionRules();
            $retentionRule->setEverySeconds($duration);
            $retentionRule->setType(BucketRetentionRules::TYPE_EXPIRE);
            $retentionRule->setShardGroupDurationSeconds(0);

            return [$retentionRule];
        }
        return [];
    }

    /**
     * @param SymfonyStyle $io
     * @param string|null $defaultName
     * @return string
     */
    protected function askForBucketName(SymfonyStyle $io, ?string $defaultName): string
    {
        return (string)$io->ask('Bucket name', $defaultName, function ($v) {
            if (empty($v)) {
                throw new \RuntimeException('The bucket name cannot be empty.');
            }
            return $v;
        });
    }

    /**
     * @param SymfonyStyle $io
     * @param string|null $defaultDescription
     * @return string
     */
    protected function askForDescription(SymfonyStyle $io, ?string $defaultDescription): string
    {
        return (string)$io->ask('Description', $defaultDescription);
    }

    /**
     * @param SymfonyStyle $io
     * @param string|null $defaultOrg
     * @return string
     */
    protected function askForOrganizationId(SymfonyStyle $io, ?string $defaultOrg): string
    {
        $choices = [];

        /** @var OrganizationsService $orgService */
        $orgService = $this->client->createService(OrganizationsService::class);
        foreach ($orgService->getOrgs()->getOrgs() as $org) {
            if ($org->getId() === $defaultOrg) {
                $defaultOrg = $org->getName();
            }
            $choices[$org->getId()] = $org->getName();
        }

        // Returns the key of the selected organization.
        return (string)$io->choice('Organisation', $choices, $defaultOrg);
    }


}
