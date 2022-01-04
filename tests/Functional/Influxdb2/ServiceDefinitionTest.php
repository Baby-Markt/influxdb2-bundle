<?php
declare(strict_types=1);

namespace Babymarkt\Symfony\Influxdb2Bundle\Tests\Functional\Influxdb2;

use InfluxDB2\Client;
use InfluxDB2\QueryApi;
use InfluxDB2\WriteApi;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ServiceDefinitionTest extends KernelTestCase
{
    /**
     * @dataProvider dataServiceDefinition
     */
    public function testDefaultServiceDefinition($className, $defaultName, $firstName, $secondName)
    {
        static::bootKernel();

        $container = static::getContainer();

        $objByClass       = $container->get($className);
        $objByDefaultName = $container->get($defaultName);
        $firstObj         = $container->get($firstName);
        $secondObj        = $container->get($secondName);

        $this->assertInstanceOf($className, $objByClass);
        $this->assertInstanceOf($className, $objByDefaultName);
        $this->assertSame($objByClass, $objByDefaultName);

        $this->assertInstanceOf($className, $firstObj);
        $this->assertInstanceOf($className, $secondObj);
        $this->assertSame($objByDefaultName, $secondObj);
    }

    public function dataServiceDefinition(): \Generator
    {
        yield [
            Client::class,
            'babymarkt_influxdb2.default_client',
            'babymarkt_influxdb2.client1_client',
            'babymarkt_influxdb2.client2_client'
        ];
        yield [
            WriteApi::class,
            'babymarkt_influxdb2.default_write_api',
            'babymarkt_influxdb2.write1_write_api',
            'babymarkt_influxdb2.write2_write_api'
        ];
        yield [
            QueryApi::class,
            'babymarkt_influxdb2.default_query_api',
            'babymarkt_influxdb2.client1_query_api',
            'babymarkt_influxdb2.client2_query_api'
        ];
    }
}
