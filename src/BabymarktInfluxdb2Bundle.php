<?php

declare(strict_types=1);

namespace Babymarkt\Symfony\Influxdb2Bundle;

use Babymarkt\Symfony\Influxdb2Bundle\DependencyInjection\Compiler\ApiRegistryPass;
use Babymarkt\Symfony\Influxdb2Bundle\DependencyInjection\Compiler\ClientRegistryPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class BabymarktInfluxdb2Bundle extends Bundle
{
    /**
     * @codeCoverageIgnore
     * @param ContainerBuilder $container
     * @return void
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new ClientRegistryPass());
        $container->addCompilerPass(new ApiRegistryPass());
    }

}
