<?php
/**
 * User: rudi <rrocha@ventureoak.com>
 */

namespace Ventureoak\DataGridBundle\DependencyInjection\CompilerPass;


use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class GridStrategiesCompilerPass implements CompilerPassInterface
{

    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $taggedIds = $container->findTaggedServiceIds('ventureoak.datagrid.strategy');
        $factory = $container->findDefinition('ventureoak.datagrid.factory');

        foreach ($taggedIds as $id => $tags) {
            $factory->addMethodCall(
                'addGrid',
                array(new Reference($id), current($tags)['alias'])
            );
        }
    }
}