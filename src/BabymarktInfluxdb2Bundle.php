<?php
/*
 * Copyright (c) 2015 Babymarkt.de GmbH - All Rights Reserved
 *
 * All information contained herein is, and remains the property of Baby-Markt.de
 * and is protected by copyright law. Unauthorized copying of this file or any parts,
 * via any medium is strictly prohibited.
 */
declare(strict_types=1);

namespace Babymarkt\Symfony\Influxdb2Bundle;

use Babymarkt\Symfony\Influxdb2Bundle\DependencyInjection\Compiler\ApiRegistryPass;
use Babymarkt\Symfony\Influxdb2Bundle\DependencyInjection\Compiler\ClientRegistryPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class BabymarktInfluxdb2Bundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ClientRegistryPass());
        $container->addCompilerPass(new ApiRegistryPass());
    }


}
