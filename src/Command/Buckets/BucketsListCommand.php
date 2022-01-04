<?php
declare(strict_types=1);

namespace Babymarkt\Symfony\Influxdb2Bundle\Command\Buckets;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'babymarkt-influxdb2:buckets:list',
    description: 'List all available buckets',
    aliases: ['babymarkt_influxdb2:buckets']
)]
class BucketsListCommand extends AbstractBucketsCommand
{
    /**
     * @inheritDoc
     */
    protected function executeCommand(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $table = $io->createTable();
        $table->setHeaderTitle('All buckets');
        $table->setHeaders(['Name', 'Id', 'Created-At', 'Description']);

        foreach ($this->service->getBuckets()->getBuckets() as $bucket) {
            $table->addRow([
                $bucket->getName(),
                $bucket->getId(),
                $bucket->getCreatedAt()->format('Y-m-d H:i:s'),
                $bucket->getDescription()
            ]);
        }

        $table->render();

        return 0;
    }
}
