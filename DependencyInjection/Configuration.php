<?php

namespace GaylordP\UploadBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('upload');

        $treeBuilder->getRootNode()
            ->children()
                ->variableNode('media_resize_enabled')
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
