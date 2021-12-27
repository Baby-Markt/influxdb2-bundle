<?php
/*
 * Copyright (c) 2015 Babymarkt.de GmbH - All Rights Reserved
 *
 * All information contained herein is, and remains the property of Baby-Markt.de
 * and is protected by copyright law. Unauthorized copying of this file or any parts,
 * via any medium is strictly prohibited.
 */

namespace Babymarkt\Symfony\InfluxDb2Bundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
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

        foreach ($config['connections'] as &$value) {
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

        $container->setParameter('babymarkt_influxdb2.default_connection', $config['default_connection']);
        $container->setParameter('babymarkt_influxdb2.connections', $config['connections']);
    }
}
