<?php
declare(strict_types=1);

namespace Babymarkt\Symfony\Influxdb2Bundle\Tests\Functional\Fixtures;

use Babymarkt\Symfony\Influxdb2Bundle\Registry\ApiRegistry;
use Babymarkt\Symfony\Influxdb2Bundle\Registry\ClientRegistry;
use InfluxDB2\Client;
use InfluxDB2\QueryApi;
use InfluxDB2\WriteApi;

/**
 * This fake class ensures that services needed for the test are not
 * deleted during the compilation of the service container.
 */
class FakeService
{
    public function __construct(ClientRegistry $clientRegistry, ApiRegistry $apiRegistry) {}

    /**
     * @required
     */
    public function setClient(Client $client): void {}

    /**
     * @required
     */
    public function setQueryApi(QueryApi $api): void {}

    /**
     * @required
     */
    public function setWriteApi(WriteApi $api): void {}
}
