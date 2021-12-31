<?php

namespace Babymarkt\Symfony\Influxdb2Bundle\DependencyInjection\Compiler;

use Babymarkt\Symfony\Influxdb2Bundle\InfluxDb\ApiRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class ApiRegistryPass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container)
    {
        if (!$container->has(ApiRegistry::class)) {
            return;
        }

        $definition = $container->findDefinition(ApiRegistry::class);

        $this->processQueryApis($container, $definition);
        $this->processWriteApis($container, $definition);
    }

    protected function processWriteApis(ContainerBuilder $container, Definition $definition)
    {
        $taggedClients = $container->findTaggedServiceIds('babymarkt_influxdb2.write_api');

        foreach ($taggedClients as $id => $tags) {
            $definition->addMethodCall('addWriteApi', [$id, new Reference($id)]);
        }
    }

    protected function processQueryApis(ContainerBuilder $container, Definition $definition)
    {
        $taggedClients = $container->findTaggedServiceIds('babymarkt_influxdb2.query_api');

        foreach ($taggedClients as $id => $tags) {
            $definition->addMethodCall('addQueryApi', [$id, new Reference($id)]);
        }
    }
}
