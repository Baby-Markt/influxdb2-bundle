<?php
declare(strict_types=1);

namespace Babymarkt\Symfony\Influxdb2Bundle\Tests\Functional\Registry;

use Babymarkt\Symfony\Influxdb2Bundle\Registry\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ClientRegistryTest extends KernelTestCase
{
    public function testServiceDefinition()
    {
        static::bootKernel();

        $container = static::getContainer();
        $this->assertInstanceOf(ClientRegistry::class, $container->get(ClientRegistry::class));
        $this->assertInstanceOf(ClientRegistry::class, $container->get('babymarkt_influxdb2.client_registry'));
    }
}
