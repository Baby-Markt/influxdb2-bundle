<?php
declare(strict_types=1);

namespace Babymarkt\Symfony\Influxdb2Bundle\Tests\Unit\DependencyInjection\Compiler;

use Babymarkt\Symfony\Influxdb2Bundle\DependencyInjection\Compiler\ClientRegistryPass;
use Babymarkt\Symfony\Influxdb2Bundle\Registry\ClientRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class ClientRegistryPassTest extends TestCase
{
    protected Container $container;
    protected MockObject|Definition $definition;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();

        $regDefStub = $this->getMockBuilder(Definition::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addMethodCall'])
            ->getMock();

        $this->container->setDefinition(id: 'babymarkt_influxdb2.client_registry', definition: $regDefStub);
        $this->container->setAlias(alias: ClientRegistry::class,
            id: new Alias(
                id: 'babymarkt_influxdb2.client_registry', public: true
            )
        );

        $this->definition = $regDefStub;
    }

    public function testProcessWithOneTaggedServiceFound()
    {
        $definition = (new Definition(\stdClass::class))
            ->setPublic(true)
            ->addTag('babymarkt_influxdb2.client');
        $serviceId  = 'some_service';
        $this->container->setDefinition($serviceId, $definition);

        $this->definition->expects($this->once())
            ->method('addMethodCall')
            ->with($this->equalTo('addClient'), $this->callback(static function ($v) {
                return $v[0] === 'some_service' && $v[1] instanceof Reference;
            }));

        $compilerPass = new ClientRegistryPass();
        $compilerPass->process($this->container);
    }

    public function testProcessWithNoTaggedServicesFound()
    {
        $this->definition->expects($this->never())->method('addMethodCall');

        $compilerPass = new ClientRegistryPass();
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

        $compilerPass = new ClientRegistryPass();
        $compilerPass->process($containerStub);

    }

    public function testProcessWithAliasForDefaultClient()
    {
        $containerStub = $this->getMockBuilder(ContainerBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['hasAlias', 'getAlias', 'has', 'findDefinition', 'findTaggedServiceIds'])
            ->getMock();

        $containerStub->expects($this->once())
            ->method('has')
            ->with(ClientRegistry::class)
            ->willReturn(true);

        $containerStub->expects($this->once())
            ->method('findDefinition')
            ->with(ClientRegistry::class)
            ->willReturn($this->definition);

        $containerStub->expects($this->once())
            ->method('findTaggedServiceIds')
            ->willReturn([]);

        $containerStub->expects($this->once())
            ->method('hasAlias')
            ->willReturn(true);

        $containerStub->expects($this->once())
            ->method('getAlias')
            ->willReturn(new Alias('some-service'));

        $this->definition->expects($this->once())
            ->method('addMethodCall')
            ->with(
                $this->equalTo('addClient'),
                $this->callback(static function ($v) {
                    return $v[0] === 'default' && $v[1] instanceof Reference;
                })
            );

        $compilerPass = new ClientRegistryPass();
        $compilerPass->process($containerStub);
    }


}
