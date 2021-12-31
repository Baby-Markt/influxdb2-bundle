<?php
/*
 * Copyright (c) 2015 Babymarkt.de GmbH - All Rights Reserved
 *
 * All information contained herein is, and remains the property of Baby-Markt.de
 * and is protected by copyright law. Unauthorized copying of this file or any parts,
 * via any medium is strictly prohibited.
 */
declare(strict_types=1);

namespace Babymarkt\Symfony\Influxdb2Bundle\DependencyInjection;

use InfluxDB2\Model\WritePrecision;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('babymarkt_influxdb2');
        $rootNode = $treeBuilder->getRootNode();

        $this->addClientSection($rootNode);
        $this->addApiSection($rootNode);

        // Global normalization and validation processes
        $rootNode
            ->beforeNormalization()
                ->always(static function ($v) {
                    return $v;
                })
            ->end();

        return $treeBuilder;
    }

    protected function addClientSection(ArrayNodeDefinition $node) {
        $node
            ->children()
                ->arrayNode('client')
                    ->children()
                        ->scalarNode('default_connection')
                            ->info('If not defined, the first connection will be taken.')
                        ->end()
                        ->arrayNode('connections')
                            ->useAttributeAsKey('name')
                            ->arrayPrototype()
                                ->children()
                                    ->scalarNode('url')
                                        ->isRequired()
                                        ->info('InfluxDB server API url (ex. http://localhost:8086).')
                                    ->end()
                                    ->scalarNode('token')
                                        ->defaultNull()
                                        ->info('Auth token.')
                                    ->end()
                                    ->scalarNode('bucket')
                                        ->defaultNull()
                                        ->info('Destination bucket for writes.')
                                    ->end()
                                    ->scalarNode('org')
                                        ->defaultNull()
                                        ->info('Organization bucket for writes.')
                                    ->end()
                                    ->scalarNode('precision')
                                        ->defaultValue(WritePrecision::NS)
                                        ->info('Precision for the unix timestamps within the body line-protocol.')
                                    ->end()
                                    ->booleanNode('verifySSL')
                                        ->defaultTrue()
                                        ->info('Turn on/off SSL certificate verification. Set to `false` to disable certificate verification.')
                                    ->end()
                                    ->booleanNode('debug')
                                        ->defaultFalse()
                                        ->info('Enable verbose logging of http requests.')
                                    ->end()
                                    ->scalarNode('logFile')
                                        ->defaultNull()
                                        ->info('Log output.')
                                    ->end()
                                    ->arrayNode('tags')
                                        ->useAttributeAsKey('name')
                                        ->scalarPrototype()->end()
                                        ->info('Default tags.')
                                    ->end()
                                    ->scalarNode('timeout')
                                        ->defaultValue(10)
                                        ->info('The number of seconds to wait while trying to connect to a server. Use 0 to wait indefinitely.')
                                    ->end()
                                    ->scalarNode('proxy')
                                        ->defaultValue(10)
                                        ->info('Pass a string to specify an HTTP proxy, or an array to specify different proxies for different protocols.')
                                    ->end()
                                    ->arrayNode('allow_redirects')
                                        ->treatTrueLike(['enabled' => true])
                                        ->treatFalseLike(['enabled' => false])
                                        ->children()
                                            ->booleanNode('enabled')->defaultNull()->end()
                                            ->scalarNode('max')->defaultValue(5)->info('Number of allowed redirects.')->end()
                                            ->booleanNode('strict')->info('Use "strict" RFC compliant redirects.')->end()
                                            ->booleanNode('referer')->info('Add a Referer header')->end()
                                            ->arrayNode('protocols')
                                                ->beforeNormalization()->ifString()->then(function ($v) { return [$v]; })->end()
                                                ->defaultValue(['http', 'https'])
                                                ->scalarPrototype()->end()
                                                ->info('Specifies which protocols are supported for redirects.')
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end() // children
                            ->end() // ArrayPrototype
                        ->end() // ArrayNode: connections
                    ->end() // children
                    ->beforeNormalization()
                        ->ifTrue(static function ($v) {
                            return is_array($v) && !array_key_exists('connections', $v);
                        })
                        ->then(static function ($v) {
                            $excludedKeys = ['default_connection' => true];
                            $connection = [];
                            foreach ($v as $key => $value) {
                                if (isset($excludedKeys[$key])) {
                                    continue;
                                }
                                $connection[$key] = $value;
                                unset($v[$key]);
                            }
                            $v['default_connection'] = isset($v['default_connection']) ? (string) $v['default_connection'] : 'default';
                            $v['connections'] = [$v['default_connection'] => $connection];
                            return $v;
                        })
                    ->end() // beforeNormalization
                ->end() // ArrayNode: client
            ->end(); // children
    }

    protected function addApiSection(ArrayNodeDefinition $node) {
       $node
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('api')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('write')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('default_option_set')->defaultNull()->end()
                                ->arrayNode('option_sets')
                                    ->useAttributeAsKey('name')
                                    ->arrayPrototype()
                                        ->children()
                                            ->scalarNode('connection')
                                                ->defaultNull()
                                                ->info('Connection to use with the write api. Defaults to the default connection.')
                                            ->end()
                                            ->arrayNode('options')
                                                ->ignoreExtraKeys(remove: false)
                                                ->children()
                                                    ->scalarNode('write_type')
                                                        ->info('(writeType) Type of write SYNCHRONOUS / BATCHING.')
                                                    ->end()
                                                    ->integerNode('batch_size')
                                                        ->info('(batchSize) The number of data point to collect in batch.')
                                                    ->end()
                                                    ->integerNode('retry_interval')
                                                        ->info('(retryInterval) The number of milliseconds to retry unsuccessful write. The retry interval is "exponentially" used when the InfluxDB server does not specify "Retry-After" header.')
                                                    ->end()
                                                    ->integerNode('jitter_interval')
                                                        ->info('(jitterInterval) The number of milliseconds before the data is written increased by a random amount.')
                                                    ->end()
                                                    ->integerNode('max_retries')
                                                        ->info('(maxRetries) The number of max retries when write fails.')
                                                    ->end()
                                                    ->integerNode('max_retry_delay')
                                                        ->info('(maxRetryDelay) Maximum delay when retrying write in milliseconds.')
                                                    ->end()
                                                    ->integerNode('max_retry_time')
                                                        ->info('(maxRetryTime) Maximum total retry timeout in milliseconds.')
                                                    ->end()
                                                    ->integerNode('exponential_base')
                                                        ->info('(exponentialBase) The base for the exponential retry delay')
                                                    ->end()
                                                ->end() // children
                                            ->end() // arrayNode: options
                                        ->end() // children
                                    ->end() // arrayPrototype
                                ->end() // arrayNode option_sets
                            ->end() // children
                            ->beforeNormalization()
                                ->ifTrue(static function ($v) {
                                    return is_array($v) && !array_key_exists('option_sets', $v);
                                })
                                ->then(static function ($v) {
                                    $excludedKeys = ['default_option_set' => true];
                                    $connection = [];
                                    foreach ($v as $key => $value) {
                                        if (isset($excludedKeys[$key])) {
                                            continue;
                                        }
                                        $connection[$key] = $value;
                                        unset($v[$key]);
                                    }
                                    $v['default_option_set'] = isset($v['default_option_set']) ? (string) $v['default_option_set'] : 'default';
                                    $v['option_sets'] = [$v['default_option_set'] => $connection];
                                    return $v;
                                })
                            ->end() // beforeNormalization
                        ->end() // arrayNode: write
                    ->end() // children
                ->end() // arrayNode: api
            ->end(); // children
    }

}
