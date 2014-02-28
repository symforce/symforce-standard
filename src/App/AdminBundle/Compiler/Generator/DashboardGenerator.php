<?php

namespace App\AdminBundle\Compiler\Generator ;

use App\AdminBundle\Compiler\CacheObject ;

/**
 * Description of DashboardGenerator
 *
 * @author loong
 */
class DashboardGenerator {
    
    public function buildDashboardGroups( \App\AdminBundle\Compiler\Generator $gen , array & $dashboard_config ){
        $default_group_name = $dashboard_config['default_group'] ;
        
        $groups   = array() ;
        
        $tr = $gen->getTransNodeByPath( $gen->app_domain , 'app.dashboard') ; 
        
        foreach( $dashboard_config['groups']  as $name => $attr ) {
            if( isset($groups[$name]) ) {
                $_item = $groups[$name] ;
            } else {
                $_item = new CacheObject\DashboardGroup( $name ) ; 
                $groups[$name]  = $_item ;
            }
            
            if( false !== $attr['position'] ) {
                $_item->setPosition( $attr['position']  ) ;
            }
            
            if( $attr['label'] ) {
                $tr->set($name, $attr['label'] );
            }
            $_item->setLabel( 'app.dashboard.' . $name  ) ;
            
            if( isset($attr['icon']) ) {
                $_item->setIcon( $attr['icon']  ) ;
            }
            
            if( isset($attr['right_side']) ) {
                $_item->setRightSide( $attr['right_side']  ) ;
            }
        }
         
        foreach($gen->admin_generators as $object ) if( $object instanceof \App\AdminBundle\Compiler\MetaType\Admin\Entity) {
            $item   = $object->dashboard ;
            if( !$item ) {
                continue ;
            }
            if( $item instanceof \App\AdminBundle\Compiler\MetaType\Admin\Dashboard ) {
                $tr     = $object->tr_node ;
                
                $group_name = $item->group ;
                if( !$group_name ) {
                    continue ;
                }
                if( true === $group_name ) {
                    $group_name = $default_group_name ;
                }
                
                if( !isset($groups[$group_name]) ) {
                    $group  = new CacheObject\DashboardGroup( $group_name ) ;
                    $groups[$group_name]  = $group ;
                } else {
                    $group  = $groups[$group_name] ;
                }
                
                $_item = new CacheObject\DashboardItem( $object->name ) ;
                $_item->setDomain( $object->tr_domain ) ;
                 
                if( null !== $item->label ) {
                    $_item->setLabel( $object->name . '.dashboard' ) ;
                    $tr->set('dashboard', $item->label ) ;
                } else {
                    $_item->setLabel( $object->name . '.label' ) ;
                }
                
                if(null !== $item->icon  ){
                     $_item->setIcon( $item->icon ) ;
                }else if ( null !== $object->icon ) {
                    $_item->setIcon( $object->icon ) ;
                }
                
                if( null !== $item->position   ) {
                    $_item->setPosition( $item->position ) ;
                }
                
                $_item->setEntity( true ) ;
                
                $group->addChild( $_item ) ;
                
                // add actions 
                foreach( $object->action_collection->children as $action ) {
                    $pos    = $action->dashboard ;
                    if( !$pos ) {
                        continue ;
                    } 
                    $_action  = new CacheObject\DashboardAction( $action->name ) ;
                    $_action->setLabel( $action->label->getPath() ) ;
                    $_action->setDomain( $action->label->getDomain() ) ; 
                    $_item->addAction( $_action ) ;
                }
                
            } else {
                echo __FILE__, ':', __LINE__, "\n"; exit; 
            }
        } else {
            echo __FILE__, ':', __LINE__, "\n"; exit; 
        }
        
        foreach($groups as $group_name => $group ) {
            $_group = $group->children ;
            $pos    = array() ;
            foreach($_group as $item) {
                if( null !== $item->postion ) {
                    $pos[$item->postion] = true  ;
                }
            }
            $_pos    = 1 ;
            foreach($_group as $item) {
                if( null === $item->postion ) {
                    while( isset($pos[$_pos]) ) {
                        $_pos++ ;
                    }
                    $item->postion = $_pos ;
                }
            }
            usort($_group, array($this, 'sort_item'));
            $group->children    = array() ;
            foreach($_group as $item ) {
                $group->children[ $item->name ] = $item ;
            }
        }
        
        return $groups ;
    }
    
    public function sort_group() {
        
    }
    
    public function sort_item(\App\AdminBundle\Compiler\CacheObject\DashboardItem $a, \App\AdminBundle\Compiler\CacheObject\DashboardItem $b) {
        return (int) $a->postion > (int) $b->postion ;
    }
    
}