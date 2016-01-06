<?php

namespace Ventureoak\DataGridBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Ventureoak\DataGridBundle\DependencyInjection\CompilerPass\GridStrategiesCompilerPass;

class VentureoakDataGridBundle extends Bundle
{
    /**
     * Builds the bundle.
     *
     * It is only ever called once when the cache is empty.
     *
     * This method can be overridden to register compilation passes,
     * other extensions, ...
     *
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    public function build(ContainerBuilder $container)
    {
//        parent::build($container);
        $container->addCompilerPass(new GridStrategiesCompilerPass());
    }

}
