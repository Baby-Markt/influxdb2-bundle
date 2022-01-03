<?php

declare(strict_types=1);

namespace Babymarkt\Symfony\Influxdb2Bundle\DependencyInjection;

use InfluxDB2\Client;
use InfluxDB2\QueryApi;
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
class BabymarktInfluxdb2Extension extends Extension
{
    protected const PREFIX = 'babymarkt_influxdb2.';

    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);

        if (empty($config['client'])) {
            return;
        }

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $config = $this->normalizeConfiguration($config);

        $this->buildClientDefinitions(container: $container, config: $config['client']);
        $this->buildWriteApiDefinitions(container: $container, config: $config['api']['write']);

        /* @todo We should remove this parameters. Currently, they are only used by unit tests. */
        $container->setParameter(self::PREFIX . 'client.default_connection', $config['client']['default_connection']);
        $container->setParameter(self::PREFIX . 'client.connections', $config['client']['connections']);
        $container->setParameter(self::PREFIX . 'api.write', $config['api']['write']);
    }

    /**
     * @param ContainerBuilder $container
     * @param array $config
     * @return void
     */
    protected function buildClientDefinitions(ContainerBuilder $container, array $config)
    {
        foreach ($config['connections'] as $name => $options) {
            $definition = (new Definition(class: Client::class, arguments: [$options]))
                ->setPublic(false)
                ->setLazy(true)
                ->addTag(self::PREFIX . 'client');

            $serviceId = sprintf(self::PREFIX . '%s_client', $name);
            $container->setDefinition($serviceId, $definition);

            // Each client needs a corresponding query api.
            $this->buildQueryApiDefinition($container, $name, $serviceId);
        }

        // Create the alias for the default client service.
        $defaultClientId = self::PREFIX . 'default_client';
        if (!$container->hasDefinition($defaultClientId)) {
            $container->setAlias(
                alias: $defaultClientId,
                id: new Alias(
                    id: sprintf(self::PREFIX . '%s_client', $config['default_connection']),
                    public: true
                )
            );
        }
    }

    /**
     * Creates the write-api service.
     * @param ContainerBuilder $container
     * @param array $config
     * @return void
     */
    protected function buildWriteApiDefinitions(ContainerBuilder $container, array $config)
    {
        foreach ($config['option_sets'] as $setName => $optionSet) {
            $connectionName = $optionSet['connection'];
            $clientId       = sprintf(self::PREFIX . '%s_client', $connectionName);
            $definition     = (new Definition(class: WriteApi::class, arguments: $optionSet['options']))
                ->setFactory([new Reference($clientId), 'createWriteApi'])
                ->setPublic(true)
                ->setLazy(true);

            $serviceId = sprintf(self::PREFIX . '%s_write_api', $setName);
            $container->setDefinition($serviceId, $definition);
        }
    }

    /**
     * Creates an query-api service definition.
     * @param ContainerBuilder $container
     * @param string $clientName
     * @param string $clientServiceId
     * @return void
     */
    protected function buildQueryApiDefinition(ContainerBuilder $container, string $clientName, string $clientServiceId)
    {
        $definition = (new Definition(QueryApi::class))
            ->setFactory([new Reference($clientServiceId), 'createQueryApi'])
            ->setPublic(true)
            ->setLazy(true);

        $serviceId = sprintf(self::PREFIX . '%s_query_api', $clientName);
        $container->setDefinition($serviceId, $definition);
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
        $camelCasedArray = [];
        foreach ($array as $key => $value) {
            $key                   = lcfirst(str_replace($separator, '', ucwords($key, $separator)));
            $camelCasedArray[$key] = $value;
        }
        return $camelCasedArray;
    }
}
