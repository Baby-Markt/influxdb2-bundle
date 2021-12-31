<?php

namespace Babymarkt\Symfony\Influxdb2Bundle\DependencyInjection\Compiler;

use Babymarkt\Symfony\Influxdb2Bundle\InfluxDb\ClientRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ClientRegistryPass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container)
    {
        if (!$container->has(ClientRegistry::class)) {
            return;
        }

        $definition = $container->findDefinition(ClientRegistry::class);

        $taggedClients = $container->findTaggedServiceIds('babymarkt_influxdb2.client');

        foreach ($taggedClients as $id => $tags) {
            $definition->addMethodCall('addClient', [$id, new Reference($id)]);
        }
    }
}
