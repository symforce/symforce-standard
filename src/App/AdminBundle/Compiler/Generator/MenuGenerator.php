<?php

namespace App\AdminBundle\Compiler\Generator ;


use App\AdminBundle\Compiler\CacheObject\Menu ;


/**
 * @author loong
 */
class MenuGenerator {
    
    public function buildMenuTree(\App\AdminBundle\Compiler\Generator $gen , array & $menu_config ){
        
        $list   = array(
                'root'  => new Menu('root') , 
        );
        
        $tr = $gen->getTransNodeByPath( $gen->app_domain , 'app.menu') ;
        
        foreach($menu_config['groups'] as $name => $attr ) {
            $_menu  = new Menu( $name ) ;
            if( false !== $attr['position'] ) {
                $_menu->setPosition( $attr['position']  ) ;
            }
            
            if( $attr['label'] ) {
                $tr->set($name, $attr['label'] );
            }
            $_menu->setLabel( 'app.menu.' . $name  ) ;
            $_menu->setDomain( $gen->app_domain ) ; 
            
            if( false !== $attr['route'] ) {
                $_menu->setRouteName( $attr['route']  ) ;
            }
            
            if( false !== $attr['url'] ) {
                $_menu->setUrl( $attr['url']  ) ;
            }
            
            if( isset($attr['divider'])  ) {
                $_menu->setDivider( $attr['divider']  ) ;
            }
            
            if( isset($attr['icon'])  ) {
                $_menu->setIcon( $attr['icon']  ) ;
            }
            
            $list[ $name ]  = $_menu ;
            
            $parent_name    = $attr['parent'] ;
            if( !isset( $list[ $parent_name ]) ) {
                $_parent    = new Menu( $parent_name ) ;
                $list[ $parent_name ]   = $_parent ;
            }
            
            $list[ $parent_name ]->addChild( $_menu ) ;
            
        }
        
        foreach($gen->admin_generators as $object ) if( $object instanceof \App\AdminBundle\Compiler\MetaType\Admin\Entity) {
            /**
             * @var \App\AdminBundle\Compiler\MetaType\Entity 
             */
            $menu   = $object->menu ;
            if( !$menu ) {
                  continue ;
            }
            if( $menu instanceof \App\AdminBundle\Compiler\MetaType\Admin\Menu ) { 
                $name = $object->name ;
                $tr   = $object->tr_node ;
                
                if( isset( $list[ $name ]) ) {
                    $_menu    = $list[ $name ] ;
                } else {
                    $_menu    = new Menu( $name ) ; 
                    $list[ $name ]  = $_menu ;
                }

                if( null !== $menu->divider  ) {
                    $_menu->setDivider( $menu->divider ) ;
                }

                if( null !== $menu->label ) {
                    $tr->set('menu', $menu->label ) ;
                    $_menu->setLabel( $name . '.menu') ;
                } else { 
                    $_menu->setLabel( $name . '.label') ;
                }
                $_menu->setDomain( $object->tr_domain ) ;
                
                if( null !== $menu->icon  ) {
                    $_menu->setIcon( $menu->icon ) ;
                } else if ( null !== $object->icon ) {
                    $_menu->setIcon( $object->icon) ;
                }

                if( null !== $menu->position  ) {
                    $_menu->setPosition( $menu->position ) ;
                }

                $_menu->admin   =  true ;

                $_parent_name   = $menu->group  ;

                if( true === $_parent_name ) { 
                    continue ; 
                    $_parent_name    = $gen->getEntityByName($name)->getParent()->getName() ;
                }

                if( isset( $list[ $_parent_name ]) ) {
                    $_parent    = $list[ $_parent_name ]  ;
                } else {
                    $_parent    = new PureObject\Menu( $_parent_name ) ;
                    $list[ $_parent_name ]   = $_parent ;
                }

                $_parent->addChild( $_menu ) ;
            } else {
                echo __FILE__, ':', __LINE__, "\n"; exit; 
            }
            
        } else {
            echo __FILE__, ':', __LINE__, "\n"; exit; 
        }
        
        /* check associated */
        
        foreach($list as $name => $menu ) {
            if( !$menu->admin ) {
                continue ;
            }
            
            if( !$menu->getParent() ) {
                continue ;
            }
            
            if( !$menu->getParent()->admin ) {
                continue ;
            }
            
            continue ;
            $_entity = $this->getAdminByName($name) ;
            
            if( $_entity->getParent()->getName() != $menu->getParent()->getName() ) {
                $_entity->throwError("memu group(%s) is not associated parent `%s`", $menu->getParent()->getName(), $_entity->getPureObject()->getParent()->getName() );
            }
        }
        
        return $list['root'] ;
    }
}