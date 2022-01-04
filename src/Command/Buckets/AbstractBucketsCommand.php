<?php
declare(strict_types=1);

namespace Babymarkt\Symfony\Influxdb2Bundle\Command\Buckets;

use Babymarkt\Symfony\Influxdb2Bundle\Registry\ClientNotFoundException;
use Babymarkt\Symfony\Influxdb2Bundle\Registry\ClientRegistry;
use InfluxDB2\Client;
use InfluxDB2\Model\Bucket;
use InfluxDB2\Model\BucketRetentionRules;
use InfluxDB2\Model\Label;
use InfluxDB2\Service\BucketsService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractBucketsCommand extends Command
{
    protected Client $client;
    protected BucketsService $service;

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
    protected final function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $this->client = $this->registry->getClient($input->getOption('client'));
        } catch (ClientNotFoundException $e) {
            $io->error($e->getMessage());
            return 3;
        }

        /** @var BucketsService $bucketsService */
        /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $this->service = $this->client->createService(BucketsService::class);

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
     * @param string|null $defaultBucket
     * @return string
     */
    protected function askForBucketId(SymfonyStyle $io, ?string $defaultBucket): string
    {
        $choices = [];

        foreach ($this->service->getBuckets()->getBuckets() as $bucket) {
            if ($bucket->getId() === $defaultBucket) {
                $defaultBucket = $bucket->getName();
            }
            $choices[$bucket->getId()] = $bucket->getName();
        }

        // Returns the key of the selected bucket.
        return (string)$io->choice('Bucket', $choices, $defaultBucket);
    }

    /**
     * @param SymfonyStyle $io
     * @param Bucket $bucket
     * @return void
     */
    protected function writeBucketTable(SymfonyStyle $io, Bucket $bucket): void
    {
        $table = $io->createTable();
        $table->setHeaderTitle('Bucket ' . $bucket->getName());
        $table->setHeaders(['Property', 'Value']);

        foreach (Bucket::getters() as $property => $getter) {
            $value = match ($property) {
                'labels' => (function () use ($bucket) {
                    /** @var Label[] $value */
                    return implode(', ', array_map(function (Label $label) {
                        return $label->getName();
                    }, (array)$bucket->getLabels()));
                })(),
                'retention_rules' => (function () use ($bucket) {
                    /** @var BucketRetentionRules[] $value */
                    return implode(PHP_EOL, array_map(function (BucketRetentionRules $rule) {
                        return (string)$rule;
                    }, (array)$bucket->getRetentionRules()));
                })(),
                'created_at', 'updated_at' => $bucket->$getter()->format('Y-m-d H:i:s'),
                default => (string)$bucket->$getter(),
            };

            $table->addRow([$property, $value]);
        }

        $table->render();
    }
}
