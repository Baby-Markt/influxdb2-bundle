<?php
declare(strict_types=1);

namespace Babymarkt\Symfony\Influxdb2Bundle\Tests\Functional\Registry;

use Babymarkt\Symfony\Influxdb2Bundle\Registry\ApiRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ApiRegistryTest extends KernelTestCase
{
    public function testServiceDefinition()
    {
        static::bootKernel();

        $container = static::getContainer();
        $this->assertInstanceOf(ApiRegistry::class, $container->get(ApiRegistry::class));
        $this->assertInstanceOf(ApiRegistry::class, $container->get('babymarkt_influxdb2.api_registry'));
    }
}
