<?php
/*
 * Copyright (c) 2015 Babymarkt.de GmbH - All Rights Reserved
 *
 * All information contained herein is, and remains the property of Baby-Markt.de
 * and is protected by copyright law. Unauthorized copying of this file or any parts,
 * via any medium is strictly prohibited.
 */

namespace Babymarkt\Symfony\InfluxDb2Bundle\DependencyInjection;

use InfluxDB2\Client;
use InfluxDB2\WriteApi;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class BabymarktInfluxDb2Extension extends Extension
{
    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $config = $this->normalizeConfiguration($config);

        $this->buildClients(container: $container, config: $config['client']);
        $this->buildWriteApi(container: $container, config: $config['api']['write']);

        $container->setParameter('babymarkt_influxdb2.client.default_connection', $config['client']['default_connection']);
        $container->setParameter('babymarkt_influxdb2.client.connections', $config['client']['connections']);
        $container->setParameter('babymarkt_influxdb2.api.write', $config['api']['write']);
    }

    protected function buildClients(ContainerBuilder $container, array $config)
    {
        foreach ($config['connections'] as $name => $options) {
            $this->createClientService(container: $container, name: $name, options: $options);
        }

        // Create the alias for the default client service.
        if (!$container->hasDefinition('babymarkt_influxdb2.default_client')) {
            $container->setAlias(
                alias: 'babymarkt_influxdb2.default_client',
                id: new Alias(
                    id: sprintf('babymarkt_influxdb2.%s_client', $config['default_connection']),
                    public: true
                )
            );
        }
    }

    /**
     * Creates the client service definitions.
     * @param string $name
     * @param array $options
     * @return void
     */
    protected function createClientService(ContainerBuilder $container, string $name, array $options)
    {
        $definition = (new Definition(class: Client::class, arguments: [$options]))
            ->setPublic(false)
            ->setLazy(true);

        $serviceId = sprintf('babymarkt_influxdb2.%s_client', $name);
        $container->setDefinition($serviceId, $definition);
    }

    /**
     * Creates the write api service.
     * @param ContainerBuilder $container
     * @param array $config
     * @return void
     */
    protected function buildWriteApi(ContainerBuilder $container, array $config)
    {
        foreach ($config['option_sets'] as $setName => $optionSet) {
            $connectionName = $optionSet['connection'];
            $clientId       = sprintf('babymarkt_influxdb2.%s_client', $connectionName);
            $definition     = (new Definition(class: WriteApi::class, arguments: $optionSet['options']))
                ->setFactory([new Reference($clientId), 'createWriteApi'])
                ->setPublic(true)
                ->setLazy(true);

            $serviceId = sprintf('babymarkt_influxdb2.%s_write_api', $setName);
            $container->setDefinition($serviceId, $definition);
        }
    }

    /**
     * Normalizes the configuration.
     * @param array $config
     * @return array
     */
    protected function normalizeConfiguration(array $config): array
    {
        $clientConfig =& $config['client'];
        $apiConfig    =& $config['api'];

        // If no default connection is given, use the first configured connection.
        if (!array_key_exists('default_connection', $clientConfig)) {
            $clientConfig['default_connection'] = array_key_first($clientConfig['connections']);
        }

        foreach ($clientConfig['connections'] as &$value) {
            // Normalize "allow_redirects" directive
            if (!array_key_exists('allow_redirects', $value)) {
                $value['allow_redirects'] = ['enabled' => true];
            }
            $value['allow_redirects'] = match ($value['allow_redirects']['enabled']) {
                false => false,
                true => true,
                default => (function ($allowRedirects) {
                    unset($allowRedirects['enabled']);
                    return $allowRedirects;
                })($value['allow_redirects'])
            };
        }

        // If no option set has been defined, we must create a default one.
        // Otherwise, no api service definition will be created.
        if (!count($apiConfig['write']['option_sets'])) {
            $apiConfig['write']['default_option_set'] = 'default';
            $apiConfig['write']['option_sets']        = [
                $apiConfig['write']['default_option_set'] => [
                    'connection' => null,
                    'options'    => []
                ]
            ];
        }

        foreach ($apiConfig['write']['option_sets'] as &$optionSet) {
            $optionSet['connection'] = $optionSet['connection'] ?? $clientConfig['default_connection'];

            // We have to convert the snake_case keys to camelCase to comply to the influxdb option keys.
            $optionSet['options'] = $this->camelizeArrayKeys($optionSet['options']);

        }

        return $config;
    }

    /**
     * Camelizes the array keys.
     * @param array $array The array to update.
     * @param string $separator The word separator. Defaults to an underscore "_".
     * @return array The camalized array.
     */
    protected function camelizeArrayKeys(array $array, string $separator = '_'): array
    {
        $camelizedArray = [];
        foreach ($array as $key => $value) {
            $key                  = lcfirst(str_replace($separator, '', ucwords($key, $separator)));
            $camelizedArray[$key] = $value;
        }
        return $camelizedArray;
    }
}
