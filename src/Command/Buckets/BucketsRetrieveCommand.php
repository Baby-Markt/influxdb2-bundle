<?php
declare(strict_types=1);

namespace Babymarkt\Symfony\Influxdb2Bundle\Command\Buckets;

use InfluxDB2\ApiException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'babymarkt-influxdb2:buckets:retrieve',
    description: 'Retrieve a existing bucket.'
)]
class BucketsRetrieveCommand extends AbstractBucketsCommand
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
            description: 'The bucket name or ID.'
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
            $this->writeBucketTable($io, $this->service->getBucketsID($bucketId));
            return 0;

        } catch (ApiException $e) {
            $io->error($e->getResponseBody());
            return 1;
        }
    }


}
