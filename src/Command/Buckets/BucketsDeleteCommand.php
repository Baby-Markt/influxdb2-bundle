<?php

namespace Babymarkt\Symfony\Influxdb2Bundle\Command\Buckets;

use InfluxDB2\ApiException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'babymarkt-influxdb2:buckets:delete',
    description: 'Delete a existing bucket.'
)]
class BucketsDeleteCommand extends AbstractBucketsCommand
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
            description: 'The bucket name or id.'
        );
    }


    /**
     * @inheritDoc
     */
    protected function executeCommand(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $bucketId = $this->askForBucketId($io, $input->getArgument('bucket'));

        if ($io->confirm('Are you sure you want to delete the bucket now?')) {
            try {
                $this->service->deleteBucketsID($bucketId);
                $io->success(sprintf('Bucket with ID "%s" successfully deleted!', $bucketId));
                return 0;

            } catch (ApiException $e) {
                $io->error($e->getResponseBody());
                return 1;
            }
        }
        return 0;
    }


}
