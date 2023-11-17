<?php
/**
 * Created by PhpStorm.
 * User: lolozere
 * Date: 01/04/20
 * Time: 19:03
 */

namespace Checlou\FlatFileCMSBundle\DependencyInjection;


use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('checlou_flat_file_cms');
        $rootNode = $treeBuilder->getRootNode();

        /*
         * We create a configuration tree with two entries : theme and content_path
         * The theme entry has an entry to set the template path of a page
         *
        */
        $rootNode
            ->children()
                ->arrayNode('theme')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('page_template')->defaultValue('page.html.twig')->end()
                    ->end()
                ->end()
                ->scalarNode('content_path')->defaultValue('%kernel.project_dir%/var/cms')->end()
            ->end();

        return $treeBuilder;
    }
}