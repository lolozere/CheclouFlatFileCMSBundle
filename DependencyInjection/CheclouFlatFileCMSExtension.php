<?php
/**
 * Created by PhpStorm.
 * User: lolozere
 * Date: 01/04/20
 * Time: 19:07
 */

namespace Checlou\FlatFileCMSBundle\DependencyInjection;


use Checlou\FlatFileCMSBundle\CMS\Pages;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class CheclouFlatFileCMSExtension extends Extension
{

    public function load(array $configs, ContainerBuilder $container)
    {

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('checlou_flat_file_cms.content_path', $config['content_path']);

        $loader = new YamlFileLoader($container, new FileLocator(dirname(__DIR__).'/Resources/config'));
        $loader->load('services.yaml');

    }

}