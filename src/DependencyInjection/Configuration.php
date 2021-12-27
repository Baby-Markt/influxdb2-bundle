<?php
/*
 * Copyright (c) 2015 Babymarkt.de GmbH - All Rights Reserved
 *
 * All information contained herein is, and remains the property of Baby-Markt.de
 * and is protected by copyright law. Unauthorized copying of this file or any parts,
 * via any medium is strictly prohibited.
 */

namespace Babymarkt\Symfony\InfluxDb2Bundle\DependencyInjection;

use InfluxDB2\Model\WritePrecision;
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
     *
     * - url: InfluxDB server API url (ex. http://localhost:8086).
     * - token: auth token
     * - bucket: destination bucket for writes
     * - org: organization bucket for writes
     * - precision: precision for the unix timestamps within the body line-protocol
     * - verifySSL: Turn on/off SSL certificate verification. Set to `false` to disable certificate verification.
     * - debug: enable verbose logging of http requests
     * - logFile: log output
     * - tags: default tags
     * - timeout: The number of seconds to wait while trying to connect to a server. Use 0 to wait indefinitely.
     * - proxy: Pass a string to specify an HTTP proxy, or an array to specify different proxies for different protocols.
     * - allow_redirects: Describes the redirect behavior for requests.
     * - ipVersion: Specifies which version of IP to use, supports 4 and 6 as possible values (UDP Writer).
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('babymarkt_influxdb2');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->beforeNormalization()
                ->ifTrue(function ($v) {
                    return is_array($v) && !isset($v['connections']);
                })
                ->then(function ($v) {
                    $excludedKeys = ['default_connection'];
                    $connection = [];
                    foreach ($v as $key => $value) {
                        if (in_array($key, $excludedKeys, true)) {
                            continue;
                        }
                        $connection[$key] = $value;
                        unset($v[$key]);
                    }
                    $v['default_connection'] = 'default';
                    $v['connections'] = ['default' => $connection];

                    return $v;
                })
            ->end()
            ->children()
                ->scalarNode('default_connection')
                    ->info('If not defined, the first connection will be taken.')
                ->end()
                ->arrayNode('connections')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype('array')
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
                        ->end()
                    ->end()
                ->end()
            ->end();


        return $treeBuilder;
    }
}
