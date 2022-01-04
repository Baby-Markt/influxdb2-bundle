<?php
declare(strict_types=1);

namespace Babymarkt\Symfony\Influxdb2Bundle\DependencyInjection\Compiler;

use Babymarkt\Symfony\Influxdb2Bundle\Registry\ClientRegistry;
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

        // Find default alias and add a reference to the default client too.
        $defaultClientId = 'babymarkt_influxdb2.default_client';
        if ($container->hasAlias($defaultClientId)) {
            $alias = $container->getAlias($defaultClientId);
            $id = (string)$alias;
            $definition->addMethodCall('addClient', ['default', new Reference($id)]);
        }

    }
}
