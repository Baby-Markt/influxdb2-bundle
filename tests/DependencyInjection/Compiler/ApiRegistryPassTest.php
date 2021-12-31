<?php

namespace DependencyInjection\Compiler;

use Babymarkt\Symfony\Influxdb2Bundle\DependencyInjection\Compiler\ApiRegistryPass;
use Babymarkt\Symfony\Influxdb2Bundle\Registry\ApiRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class ApiRegistryPassTest extends TestCase
{
    protected Container $container;
    protected MockObject|Definition $definition;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();

        $definitionStub = $this->getMockBuilder(Definition::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addMethodCall'])
            ->getMock();

        $this->container->setDefinition(id: 'babymarkt_influxdb2.api_registry', definition: $definitionStub);
        $this->container->setAlias(alias: ApiRegistry::class,
            id: new Alias(
                id: 'babymarkt_influxdb2.api_registry', public: true
            )
        );

        $this->definition = $definitionStub;
    }

    public function testProcessWithOneTaggedServiceFound()
    {
        $definition = (new Definition(\stdClass::class))
            ->setPublic(true)
            ->addTag('babymarkt_influxdb2.write_api')
            ->addTag('babymarkt_influxdb2.query_api');
        $serviceId  = 'some_service';
        $this->container->setDefinition($serviceId, $definition);

        $this->definition->expects($this->exactly(2))
            ->method('addMethodCall')
            ->withConsecutive(
                [
                    $this->equalTo('addQueryApi'), $this->callback(static function ($v) {
                        return $v[0] === 'some_service' && $v[1] instanceof Reference;
                    })
                ],
                [
                    $this->equalTo('addWriteApi'), $this->callback(static function ($v) {
                    return $v[0] === 'some_service' && $v[1] instanceof Reference;
                })
                ]
            );

        $compilerPass = new ApiRegistryPass();
        $compilerPass->process($this->container);
    }

    public function testProcessWithNoTaggedServicesFound()
    {
        $this->definition->expects($this->never())->method('addMethodCall');

        $compilerPass = new ApiRegistryPass();
        $compilerPass->process($this->container);
    }

    public function testProcessWithNoRegistryServiceAvailable()
    {
        $containerStub = $this->getMockBuilder(ContainerBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findDefinition', 'has'])
            ->getMock();

        $containerStub->expects($this->once())->method('has')->willReturn(false);
        $containerStub->expects($this->never())->method('findDefinition');

        $compilerPass = new ApiRegistryPass();
        $compilerPass->process($containerStub);

    }
}
