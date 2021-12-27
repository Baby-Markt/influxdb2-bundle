<?php

namespace DependencyInjection;

use Babymarkt\Symfony\InfluxDb2Bundle\DependencyInjection\BabymarktInfluxDb2Extension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class BabymarktInfluxDb2ExtensionTest extends TestCase
{
    protected BabymarktInfluxDb2Extension $extension;
    protected Container $container;
    protected string $root;

    protected function setUp(): void
    {
        $this->extension = new BabymarktInfluxDb2Extension();
        $this->container = new ContainerBuilder();
        $this->root      = 'babymarkt_influxdb2.';

        $this->container->setParameter('kernel.bundles', []);
        $this->container->setParameter('kernel.debug', 'false');
    }

    public function testDefaultConfigurationValues()
    {
        $config = [
            'url'             => 'http://localhost:8086'
        ];
        $this->extension->load([$config], $this->container);

        $defaultConnection = $this->container->getParameter($this->root . 'default_connection');
        $this->assertEquals('default', $defaultConnection);

        $connections = $this->container->getParameter($this->root . 'connections');

        $this->assertEquals($config['url'], $connections['default']['url']);
    }

    public function testDisableAllowRedirects()
    {
        $config = [
            'url'             => 'http://localhost:8086',
            'allow_redirects' => false
        ];
        $this->extension->load([$config], $this->container);

        $connections = $this->container->getParameter($this->root . 'connections');

        $this->assertFalse($connections['default']['allow_redirects']);
    }

    public function testEnableAllowRedirects()
    {
        $config = [
            'url'             => 'http://localhost:8086',
            'allow_redirects' => true
        ];
        $this->extension->load([$config], $this->container);

        $connections = $this->container->getParameter($this->root . 'connections');

        $this->assertTrue($connections['default']['allow_redirects']);
    }

    public function testCustomAllowRedirects()
    {
        $config = [
            'url'             => 'http://localhost:8086',
            'allow_redirects' => [
                'max'       => 100,
                'strict'    => true,
                'referer'   => false,
                'protocols' => 'http'
            ]
        ];
        $this->extension->load([$config], $this->container);

        $connections = $this->container->getParameter($this->root . 'connections');

        $this->assertIsArray($connections['default']['allow_redirects']);
        $this->assertSame(100, $connections['default']['allow_redirects']['max']);
        $this->assertTrue($connections['default']['allow_redirects']['strict']);
        $this->assertFalse($connections['default']['allow_redirects']['referer']);
        $this->assertEquals(['http'], $connections['default']['allow_redirects']['protocols']);
    }


}
