<?php declare(strict_types = 1);
/*
 * This file is part of the Bukashk0zzzLiipImagineSerializationBundle
 *
 * (c) Denis Golubovskiy <bukashk0zzz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bukashk0zzz\LiipImagineSerializationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\NodeParentInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the configuration class
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree.
     */
    public function getConfigTreeBuilder(): NodeParentInterface
    {
        $treeBuilder = new TreeBuilder('bukashk0zzz_liip_imagine_serialization');

        $rootNode = \method_exists($treeBuilder, 'getRootNode')
            ? $treeBuilder->getRootNode()
            : $treeBuilder->root('bukashk0zzz_liip_imagine_serialization');

        $rootNode
            ->children()
            ->scalarNode('includeHost')->defaultValue(true)->end()
            ->scalarNode('vichUploaderSerialize')->defaultValue(true)->end()
            ->scalarNode('includeOriginal')->defaultValue(false)->end()
            ->scalarNode('includeHostForOriginal')->defaultValue(false)->end()
            ->scalarNode('originUrlNormalizer')->defaultValue(null)->end()
            ->scalarNode('filteredUrlNormalizer')->defaultValue(null)->end()
        ;

        return $treeBuilder;
    }
}
