<?php

namespace App\AdminBundle\Compiler\Cache ;

/**
 *
 * @author loong
 */
trait AdminMenu {
    
    /**
     * @return bool
     */
    protected $show_menu_tree ;
    
    /**
     * @return \Knp\Menu\MenuItem
     */
    protected $action_menu ;
    
    /**
     * @return \Knp\Menu\MenuItem
     */
    protected $action_bar_menu ;
    
    /**
     * @return \Knp\Menu\MenuItem
     */
    protected $tree_menu ;

    public function isShowMenuTree() {
        if( $this->tree ) {
            return true ;
        }
        if( null === $this->action_menu  ) {
            if( $this->route_parent || !empty($this->_admin_route_children) && $this->route_object_id ) {
                $this->getActionMenu() ;
            }
        }
        return $this->show_menu_tree ;
    }
    
    /**
     * @return \Knp\Menu\MenuItem
     */
    public function getActionBarMenu() {
        if( null === $this->action_bar_menu  ) {
            $factory    = $this->getService('knp_menu.factory') ;
            $menu   = $factory->createItem('root', array(
                    "childrenAttributes"    => array(
                        "class"    => "action_menu_bar" ,
                    ) ,
                )) ;
            $this->action_bar_menu  = $menu ;
            $this->configureBarMenu( $menu ) ;
        }
        return $this->action_bar_menu  ;
    }
    
    /**
     * @return \Knp\Menu\MenuItem
     */
    public function getActionMenu(){
        
        if( null !== $this->action_menu  ) {
            return $this->action_menu ;
        }
        $factory    = $this->getService('knp_menu.factory') ;
        $menu   = $factory->createItem('root', array(
                "childrenAttributes"    => array(
                    "class"    => "action_menu_root" ,
                ) ,
            )) ;
        $this->action_menu  = $menu ;
        $this->configureActionMenu($menu, true ) ;
        foreach( $menu->getIterator() as $item ) {
            if( $item->count() ) {
                $this->show_menu_tree   = true ;
                break ;
            }
        }
        return $menu ;
    }
    
    /**
     * @return \Knp\Menu\MenuItem
     */
    public function getTreeMenu(){
        if( ! $this->tree ) {
            return ;
        }
        if( null !== $this->tree_menu  ) {
            return $this->tree_menu ;
        }
        $factory    = $this->getService('knp_menu.factory') ;
        $this->tree_menu = $factory->createItem('root', array(
                "childrenAttributes"    => array(
                    "class"    => "admin_tree_root" ,
                ) ,
            )) ;
        $object = $this->getTreeObject() ;
        $root   = $this->configureTreeMenu( $object,  $factory ) ;
        $this->tree_menu->addChild( $root ) ;
        return $this->tree_menu ;
    }
    
    protected function configureBarMenu(\Knp\Menu\MenuItem $menu ) {
        
        foreach($this->action_maps as $action_name => $action_class){
            $action = $this->getAction($action_name) ;
            if( false !== $action->isRequestObject()  ) {
                continue ;
            }
            if( $action->isBatchAction() ) {
                continue ;
            }
            $action->configureMenu($menu) ;
        }
        
    }
    
    protected function configureActionMenu(\Knp\Menu\MenuItem $root_menu, $with_parent = false ) {
        
        if( $with_parent && $this->route_parent ) {
            $this->route_parent->configureActionMenu( $root_menu , true ) ;
            return ;
        }
        
        $route_object  = null ;
        if ( $this->route_object_id ) {
            $route_object = $this->getRouteObject() ;
        } else if($this->tree && $this->tree_object_id){
            $route_object = $this->getTreeObject() ;
        }
        
        
        $options = array() ;
        if( $this->auth('list', $route_object ) ) {
            $options['uri'] = $this->getAction('list', $route_object)->path() ;
        } else {
            $options['labelAttributes']['class'] = 'app_action_tree_nolink' ;
        }
        $menu  = $root_menu->addChild( $this->getLabel() , $options ) ;
        if( $this->getAction('list')->isRouteAction() ) {
            $menu->setCurrent( true ) ;
        }

        foreach($this->action_maps as $action_name => $action_class){
            $action = $this->getAction($action_name) ;
            if( false !== $action->isRequestObject()  ) {
                continue ;
            }
            if( $action->isBatchAction() || $action->isListAction() ) {
                continue ;
            }
            $action->configureMenu($menu) ;
        }
        
        if( $this->route_object_id ) {
            
            $options = array() ;
            $object = $this->getRouteObject() ;
            if( $this->hasAction('view') && $this->auth('view', $route_object) ) {
                $options['uri'] = $this->getAction('view')->path() ;
            } else {
                $options['labelAttributes']['class'] = 'app_action_tree_nolink' ;
            }
            $_menu  = $menu->addChild( $this->string( $object ) , $options ) ;
            if( $this->hasAction('view')  && $this->getAction('view')->isRouteAction() ) {
                $_menu->setCurrent( true ) ;
            }
            
            foreach($this->action_maps as $action_name => $action_class){
                $action = $this->getAction($action_name) ;
                if( false === $action->isRequestObject()  ) {
                    continue ;
                }
                if( $action->isBatchAction() || $action->isViewAction() ) {
                    continue ;
                }
                if( $action->isPageAction() && !$this->page_one2one_map ) {
                    continue ;
                }
                $action->configureMenu($_menu, true ) ; 
            }
            
            if( $_menu->count() < 1 ) {
                $menu->removeChild( $_menu ) ;
            }
        }
        
        $children   = $this->getRouteChildren() ;
        foreach( $children as $property_name => $child ) {
            if( $this->workflow ) {
                if( $route_object ) {
                    $status = $this->getObjectWorkflowStatus( $route_object ) ;
                } else {
                    if( ! $this->_route_workflow_status ) {
                        throw new \Exception("big error");
                    }
                    $status     =  $this->workflow['status'][ $this->_route_workflow_status ];
                }
                if( ! $status['properties'] || !isset($status['properties'][$property_name]) ) {
                    continue ;
                }
            }
            $child->configureActionMenu( $root_menu ) ;
        }
    }
    
    /**
     * @return \Knp\Menu\MenuItem
     */
    protected function configureTreeMenu( $object , \Knp\Menu\MenuFactory $factory, \Knp\Menu\MenuItem $child = null ) {
        if( !$object ) {
            $options    = array() ;
            if( $this->auth('list', $object) ) {
                $options['uri'] = $this->path('list', 0 ) ;
            }  else {
                $options['labelAttributes']['class'] = 'app_action_tree_nolink' ;
            } 
            $menu   = $factory->createItem( $this->trans('app.tree.root',  array(
                '%admin%'   => $this->getLabel() ,
            ), $this->app_domain ), $options) ;
            if( !$this->tree_object_id ) {
                $menu->setCurrent( true ) ;
            } 
            if( $child ) {
                $menu->addChild( $child ) ;
            }
            return $menu ;
        }
        
        $options    = array() ;
        if( $this->auth('list', $object) ) {
            $options['uri'] = $this->path('list', $object) ;
        }  else {
            $options['labelAttributes']['class'] = 'app_action_tree_nolink' ;
        }
        
        $menu   = $factory->createItem(  $this->string($object), $options) ;
        if( $this->getReflectionProperty( $this->property_id_name )->getValue( $object ) == $this->tree_object_id ) {
            $menu->setCurrent( true ) ;
        }
        if( $child ) {
            $menu->addChild( $child ) ;
        }
        
        $parent = $this->getReflectionProperty( $this->tree['parent'] )->getValue( $object ) ;
        return $this->configureTreeMenu( $parent, $factory, $menu ) ;
    }
    
}
