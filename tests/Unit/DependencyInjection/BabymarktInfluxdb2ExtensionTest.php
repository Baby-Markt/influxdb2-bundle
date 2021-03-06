<?php
declare(strict_types=1);

namespace Babymarkt\Symfony\Influxdb2Bundle\Tests\Unit\DependencyInjection;

use Babymarkt\Symfony\Influxdb2Bundle\DependencyInjection\BabymarktInfluxdb2Extension;
use InfluxDB2\WriteType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class BabymarktInfluxdb2ExtensionTest extends TestCase
{
    protected BabymarktInfluxdb2Extension $extension;
    protected Container $container;
    protected string $root;

    protected function setUp(): void
    {
        $this->extension = new BabymarktInfluxdb2Extension();
        $this->container = new ContainerBuilder();
        $this->root      = 'babymarkt_influxdb2.';

        $this->container->setParameter('kernel.bundles', []);
        $this->container->setParameter('kernel.debug', 'false');
    }

    public function testDefaultConfigurationValues()
    {
        $config = [
            'client' => [
                'url'    => 'http://localhost:8086',
                'bucket' => 'test',
                'org'    => 'test'
            ]
        ];
        $this->extension->load([$config], $this->container);

        $defaultConnection = $this->container->getParameter($this->root . 'client.default_connection');
        $this->assertEquals('default', $defaultConnection);

        $connections = $this->container->getParameter($this->root . 'client.connections');

        $this->assertEquals($config['client']['url'], $connections['default']['url']);
        $this->assertTrue($this->container->has($this->root . 'default_client'));
    }

    public function testWithoutConfiguration()
    {
        $config = [];
        $this->extension->load([$config], $this->container);
        $this->assertFalse($this->container->has($this->root . 'default_client'));
        $this->assertFalse($this->container->has($this->root . 'default_query_api'));
    }

    public function testDisableAllowRedirects()
    {
        $config = [
            'client' => [
                'url'             => 'http://localhost:8086',
                'allow_redirects' => false,
                'bucket'          => 'test',
                'org'             => 'test'
            ]
        ];
        $this->extension->load([$config], $this->container);

        $connections = $this->container->getParameter($this->root . 'client.connections');

        $this->assertFalse($connections['default']['allow_redirects']);
    }

    public function testEnableAllowRedirects()
    {
        $config = [
            'client' => [
                'url'             => 'http://localhost:8086',
                'allow_redirects' => true,
                'bucket'          => 'test',
                'org'             => 'test'
            ]
        ];
        $this->extension->load([$config], $this->container);

        $connections = $this->container->getParameter($this->root . 'client.connections');

        $this->assertTrue($connections['default']['allow_redirects']);
    }

    public function testCustomAllowRedirects()
    {
        $config = [
            'client' => [
                'url'             => 'http://localhost:8086',
                'bucket'          => 'test',
                'org'             => 'test',
                'allow_redirects' => [
                    'max'       => 100,
                    'strict'    => true,
                    'referer'   => false,
                    'protocols' => 'http'
                ]
            ]
        ];
        $this->extension->load([$config], $this->container);

        $connections = $this->container->getParameter($this->root . 'client.connections');

        $this->assertIsArray($connections['default']['allow_redirects']);
        $this->assertSame(100, $connections['default']['allow_redirects']['max']);
        $this->assertTrue($connections['default']['allow_redirects']['strict']);
        $this->assertFalse($connections['default']['allow_redirects']['referer']);
        $this->assertEquals(['http'], $connections['default']['allow_redirects']['protocols']);
    }

    public function testMultipleConnections()
    {
        $config = [
            'client' => [
                'default_connection' => 'c1',
                'connections'        => [
                    'c1' => [
                        'url'    => 'http://localhost:8086',
                        'bucket' => 'test',
                        'org'    => 'test'
                    ],
                    'c2' => [
                        'url'    => 'http://localhost:8086',
                        'bucket' => 'test',
                        'org'    => 'test'
                    ]
                ]
            ]
        ];
        $this->extension->load([$config], $this->container);

        $defaultConnection = $this->container->getParameter($this->root . 'client.default_connection');
        $connections       = $this->container->getParameter($this->root . 'client.connections');
        $this->assertCount(2, $connections);
        $this->assertArrayHasKey('c1', $connections);
        $this->assertArrayHasKey('c2', $connections);
        $this->assertArrayNotHasKey('default', $connections);
        $this->assertEquals('c1', $defaultConnection);
        $this->assertTrue($this->container->has($this->root . 'c1_client'));
        $this->assertTrue($this->container->has($this->root . 'c2_client'));
        $this->assertTrue($this->container->has($this->root . 'c1_query_api'));
        $this->assertTrue($this->container->has($this->root . 'c2_query_api'));

    }

    public function testMultipleConnectionsNotHasDefault()
    {
        $config = [
            'client' => [
                'connections' => [
                    'c1' => [
                        'url'    => 'http://localhost:8086',
                        'bucket' => 'test',
                        'org'    => 'test'
                    ],
                    'c2' => [
                        'url'    => 'http://localhost:8086',
                        'bucket' => 'test',
                        'org'    => 'test'
                    ]
                ]
            ]
        ];
        $this->extension->load([$config], $this->container);

        $defaultConnection = $this->container->getParameter($this->root . 'client.default_connection');
        $connections       = $this->container->getParameter($this->root . 'client.connections');
        $this->assertCount(2, $connections);
        $this->assertArrayHasKey('c1', $connections);
        $this->assertArrayHasKey('c2', $connections);
        $this->assertArrayNotHasKey('default', $connections);
        $this->assertEquals('c1', $defaultConnection);
    }

    public function testMultipleConnectionsHasDefault()
    {
        $config = [
            'client' => [
                'default_connection' => 'c2',
                'connections'        => [
                    'c1' => [
                        'url'    => 'http://localhost:8086',
                        'bucket' => 'test',
                        'org'    => 'test'
                    ],
                    'c2' => [
                        'url'    => 'http://localhost:8086',
                        'bucket' => 'test',
                        'org'    => 'test'
                    ]
                ]
            ]
        ];
        $this->extension->load([$config], $this->container);

        $this->assertEquals('c2', $this->container->getParameter($this->root . 'client.default_connection'));
        $this->assertTrue($this->container->hasAlias($this->root . 'default_client'));

        // Check if the default client is an alias to the correct connection.
        $alias = $this->container->getAlias($this->root . 'default_client');
        $this->assertSame($this->root . 'c2_client', (string)$alias);
    }

    public function testWriteApiDefaultConfig()
    {
        $config = [
            'client' => [
                'url'    => 'http://localhost:8086',
                'bucket' => 'test',
                'org'    => 'test'
            ]
        ];
        $this->extension->load([$config], $this->container);

        $this->assertTrue($this->container->hasDefinition($this->root . 'default_write_api'));
    }

    public function testWriteApiWithWriteOptions()
    {
        $config = [
            'client' => [
                'connections' => [
                    'c1' => [
                        'url'    => 'http://localhost:8086',
                        'bucket' => 'test',
                        'org'    => 'test'
                    ],
                    'c2' => [
                        'url'    => 'http://localhost:8086',
                        'bucket' => 'test',
                        'org'    => 'test'
                    ]
                ]
            ],
            'api'    => [
                'write' => [
                    'connection' => 'c2',
                    'options'    => [
                        'write_type' => WriteType::BATCHING,
                        'extra_key'  => 123
                    ]
                ]
            ]
        ];
        $this->extension->load([$config], $this->container);

        $config      = $this->container->getParameter($this->root . 'api.write');
        $defaultName = $config['default_option_set'];
        $optionSets  = $config['option_sets'];

        $this->assertEquals('c2', $optionSets[$defaultName]['connection']);
        $this->assertEquals(WriteType::BATCHING, $optionSets[$defaultName]['options']['writeType']);
        $this->assertEquals(123, $optionSets[$defaultName]['options']['extraKey']);
    }
}
