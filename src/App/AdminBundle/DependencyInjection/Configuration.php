<?php

namespace App\AdminBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;


class Configuration implements ConfigurationInterface
{
    
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('app_admin');
        
        $rootNode
            ->children()
                ->arrayNode('route')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('login_path')->cannotBeEmpty()->defaultValue('app_admin_login')->end()
                        ->scalarNode('logout_path')->cannotBeEmpty()->defaultValue('app_admin_logout')->end()
                        ->scalarNode('dashboard_path')->cannotBeEmpty()->defaultValue('app_admin_dashboard')->end()
                        ->scalarNode('brand_path')->cannotBeEmpty()->defaultValue('app_admin_dashboard')->end()
                    ->end()
                ->end()
                
                ->arrayNode('language')
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->useAttributeAsKey('name')
                    ->prototype('scalar')->end()
                ->end()
                
            ->end()
            ->append($this->addAdminNode())
            ->append($this->addDashboardNode()) 
            ->append($this->addMenuNode())
            ->append($this->addFormNode())
            ;

        return $treeBuilder;
    }
    
    
    private function addAdminNode(){
        $builder = new TreeBuilder();
        $node = $builder->root('admin');

        $node
            ->addDefaultsIfNotSet()
            ->children()
                 ->scalarNode('domain')->cannotBeEmpty()->defaultValue('AppAdminBundle')->end()
            ->end()
        ;

        return $node;
    }
    
    private function addDashboardNode(){
        $builder = new TreeBuilder();
        $node = $builder->root('dashboard');

        $node
            ->children()
                 ->scalarNode('default_group')->defaultValue('default')->end()
                 ->arrayNode('groups')
                    /*
                    ->validate()
                        ->ifTrue(function($v) { return isset($v['default']); })
                        ->thenInvalid('can not use default as group name "%s"')
                    ->end()
                    */
                    ->useAttributeAsKey('id')
                    ->prototype('array')
                    ->children()
                        ->scalarNode('label')->defaultFalse()->end()
                        ->booleanNode('right_side')->defaultFalse()->end()
                        ->enumNode('position')
                                ->values( array('left', 'right' ) )
                                ->defaultValue('left')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $node;
    }
    
    private function addMenuNode(){
        $builder = new TreeBuilder();
        $node = $builder->root('menu');

        $node
            ->isRequired()
            ->children()
                 
            ->end()
            ->append($this->addMenuGroupsNode())
        ;

        return $node;
    }
    
    private function addMenuGroupsNode(){
        $builder = new TreeBuilder();
        $node = $builder->root('groups');
        
        $node
            ->validate()
                ->ifTrue(function($v) { return isset($v['root']); })
                ->thenInvalid('can not use root as group name "%s"')
            ->end()
            ->useAttributeAsKey('id')
            // ->requiresAtLeastOneElement()
            ->prototype('array')
            ->children()
                ->scalarNode('parent')->defaultValue('root')->end()
                ->scalarNode('label')->defaultFalse()->end()
                ->scalarNode('route')->defaultFalse()->end()
                ->scalarNode('url')->defaultFalse()->end()
                ->integerNode('position')->defaultFalse()->end()
                ->scalarNode('icon')->end()
                ->booleanNode('divider')->end()
            ->end()
        ;
        return $node;
    }
    
    private function addFormNode(){
        $builder = new TreeBuilder();
        $node = $builder->root('form');

        $node
            ->addDefaultsIfNotSet()
            ->children()
                 
            ->end()
                                
            ->append($this->addFormTypeNode())
        ;

        return $node;
    }
    
    
    private function addFormTypeNode(){
        $builder = new TreeBuilder();
        $node = $builder->root('type');
        
        $node
            ->useAttributeAsKey('name')
            ->prototype('scalar')->end()
        ;
        return $node;
    }
    
}
