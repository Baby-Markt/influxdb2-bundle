<?php
declare(strict_types=1);

namespace Babymarkt\Symfony\Influxdb2Bundle\Command\Buckets;

use InfluxDB2\ApiException;
use InfluxDB2\Model\BucketRetentionRules;
use InfluxDB2\Model\PatchBucketRequest;
use InfluxDB2\Model\PatchRetentionRule;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'babymarkt-influxdb2:buckets:update',
    description: 'Update a existing bucket.'
)]
class BucketsUpdateCommand extends AbstractBucketsCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        parent::configure();

        $this->addArgument(
            name: 'bucket',
            mode: InputArgument::OPTIONAL,
            description: 'The bucket name or ID to update.'
        );

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
            name: 'duration',
            mode: InputOption::VALUE_OPTIONAL,
            description: 'The duration in seconds for how long data will be kept in the database. 0 means infinite.',
            default: 0
        );
    }


    /**
     * @inheritDoc
     */
    protected function executeCommand(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $bucketId = $this->askForBucketId($io, $input->getArgument('bucket'));

        try {
            $bucket = $this->service->getBucketsID($bucketId);

        } catch (ApiException $e) {
            $io->error($e->getResponseBody());
            return 2;
        }

        $patchBucketRequest = (new PatchBucketRequest())
            ->setName($this->askForBucketName(
                io: $io,
                defaultName: $input->getOption('name') ?? $bucket->getName()
            ))
            ->setDescription($this->askForDescription(
                io: $io,
                defaultDescription: $input->getOption('description') ?? $bucket->getDescription()
            ))
            ->setRetentionRules($this->askForRetentionRules(
                io: $io,
                defaultDuration: (int)$input->getOption('duration') ?? $bucket->getRetentionRules()[0]?->getEverySeconds()
            ));

        try {
            $bucket = $this->service->patchBucketsID($bucketId, $patchBucketRequest);
            $io->success(sprintf('Bucket "%s" successfully updated!', $bucket->getName()));
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
            $retentionRule = new PatchRetentionRule();
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


}
