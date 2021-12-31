<?php

namespace Registry;

use Babymarkt\Symfony\Influxdb2Bundle\Registry\ClientNotFoundException;
use Babymarkt\Symfony\Influxdb2Bundle\Registry\ClientRegistry;
use InfluxDB2\Client;
use PHPUnit\Framework\TestCase;

class ClientRegistryTest extends TestCase
{
    public function testRegistry()
    {
        $clientStub = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $registry = new ClientRegistry();
        $registry->addClient('babymarkt_influxdb2.default_client', $clientStub);
        $registry->addClient('some_client', $clientStub);

        $this->assertTrue($registry->hasClient('default'));
        $this->assertTrue($registry->hasClient('some_client'));

        $this->assertSame($clientStub, $registry->getClient('default'));
        $this->assertSame($clientStub, $registry->getClient('some_client'));
    }

    public function testWithHasNoClient()
    {
        $registry = new ClientRegistry();
        $this->assertFalse($registry->hasClient('some-client'));
    }

    public function testWithNoClientFound()
    {
        $this->expectException(ClientNotFoundException::class);
        $registry = new ClientRegistry();
        $registry->getClient('some-client');
    }
}
