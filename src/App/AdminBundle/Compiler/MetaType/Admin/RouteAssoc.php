<?php

namespace App\AdminBundle\Compiler\MetaType\Admin ;

use App\AdminBundle\Compiler\Annotation\Annotation ;

/**
 */
class RouteAssoc extends \App\AdminBundle\Compiler\MetaType\Type {
    
    /**
     * @var Entity 
     */
    public $admin ;
    
    /**
     * @var array 
     */
    protected $_named_children = array() ;
    
    /**
     * @var array 
     */
    public $_anonymous_children = array() ;
    
    /**
     * @var array 
     */
    public $_route_children = array() ;
    
    /**
     * @var array 
     */
    public $_route_parents = array() ;
    
    /**
     * @var array 
     */
    public $_children = array() ;
    
    /**
     * @var array 
     */
    public $_parents = array() ;
    
    public $route_root ;
    
    public $_tree_deep ;
    public $_tree_path ;
    public $_tree_parent ;
    
    public function __construct(Entity $admin ) {
        $this->admin    = $admin ;
    }
    
    private $lazy_initialized ;
    public function lazyInitialize() {
        if( $this->lazy_initialized  ) {
            throw new \Exception('big error') ;
        }
        $this->lazy_initialized = true ;
        
        if( $this->admin->dashboard ) {
            $this->route_root = true ;
        } else if ( $this->admin->menu && $this->admin->menu->group && !$this->admin->generator->hasAdminName( $this->admin->menu->group ) ) {
            $this->route_root = true ;
        }
    }
    
    private $parent_initialized ;
    public function parentInitialize(){
        if( $this->parent_initialized || $this->route_initialized || !$this->lazy_initialized  ) {
            throw new \Exception('big error') ;
        }
        $this->parent_initialized   = true ;
        foreach($this->_children as $child_admin_name => $none ) {
            $child_admin    = $this->admin->generator->getAdminByName( $child_admin_name ) ;
            $find_parent   = false ;
            foreach($child_admin->_orm_map as $property => $config ) {
                if( $config[2] != \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE ) {
                    continue ;
                }
                if( $config[1] === $this->admin->name ) {
                    $child_admin->_route_assoc->_parents[ $property ] = $this->admin->name ;
                    $find_parent    = true ;
                }
            }
            if( !$find_parent ) {
                throw new \Exception(sprintf("big error: admin(%s) not find child(%s)", $this->admin->name, $child_admin_name));
            }
        }
    }
    
    private function getRootParent( & $deep, array & $path  ) {
        if( empty($this->_parents) ) {
            return $this->admin->name ; 
        } 
        $max_deep   = 0 ;
        $max_parents = array() ;
        foreach($this->_parents as $my_property => $parent_name ) {
            $parent_admin    = $this->admin->generator->getAdminByName( $parent_name ) ;
            $_deep   =  0 ;
            $_path   = array() ;
            $parent_name = $parent_admin->getRootParent( $_deep, $_path ) ;
            if( $_deep < $max_deep ) {
                continue ;
            }
            if( $_deep > $max_deep ) {
                $max_parents  = array() ;
            }
            $max_parents[ $root_admin_name ] = $_path ;
        }
        if( empty($roots) ) {
            throw new \Exception("big error!") ;
        }
        $keys   = array_keys( $roots ) ;
        sort( $keys ) ;
        $root_name = $keys[0] ;
        
        return $root_name ;
    }
    

    private $route_initialized ;
    public function routeInitialize(){
        if( $this->route_initialized || ! $this->lazy_initialized || !$this->parent_initialized  ) {
            throw new \Exception('big error') ;
        }
        $this->route_initialized = true ;
        
        $children   = array() ;
        foreach($this->_named_children as $my_property => $o ) {
            $child_name = $o[0] ;
            $child_property = $o[1] ;
            if( !isset($children[ $child_name ]) ) {
                $children[ $child_name ]    = array() ;
            }
            $children[ $child_name ][] = array( 
                    'child_property' => $child_property , 
                    'my_property'  => $my_property 
                 ) ; 
        }
        
        foreach($this->_anonymous_children as $o ) {
            $child_name = $o[0] ;
            $child_property = $o[1] ;
            $_my_property   = null ;
            if( isset($children[ $child_name ]) ) {
                foreach($children[ $child_name ] as $c ) {
                    if( $child_property === $c['child_property'] ) {
                        $_my_property   = $c['my_property'] ;
                        break ;
                    }
                }
            } 
            if( !$_my_property ) {
                $children[ $child_name ][] = array( 
                    'child_property' => $child_property , 
                    'my_property'  => $_my_property 
                 ) ; 
            }
        }
        
        $_children  = array() ;
        foreach($this->admin->generator->admin_deep_order as $child_name ) {
            if( !isset($children[$child_name]) ) {
                continue ;
            }
            $list   = $children[$child_name] ;
            if( count($list) > 1 ) {
                $_children[$child_name] = array(
                    1 ,
                    array() , 
                );
                foreach($list as $c) {
                    if( isset($_children[$child_name][1][ $c['child_property'] ]) ) {
                        throw new \Exception(sprintf("big error, admin(%s) has duplicate child(%s) property(%s)", $this->admin->name, $child_name, $c['child_property']  ));
                    }
                    $_children[$child_name][1][ $c['child_property'] ] = $c['my_property'] ;
                }
            } else {
                foreach($list as $c) {
                    $_children[$child_name] = array(
                        0 ,
                        array( $c['child_property'], $c['my_property'] ) , 
                    );
                }
            }
        }
        
        $this->_route_children  = $_children ;
        
        // add parent
        foreach( $_children as $child_name => $o ) {
            $child_admin    = $this->admin->generator->getAdminByName( $child_name ) ;
            if( $o[0] ) { // multi property for same admin
                foreach($o[1]  as $_match ) {
                    $child_admin->_route_assoc->_route_parents[ $this->admin->name ][] = $_match;
                }
            } else { // one property for same admin
                 $child_admin->_route_assoc->_route_parents[ $this->admin->name ][] = $o[1] ;
            }
        }
        
        $_anonymous_children   = array() ;
        foreach( $_children as $child_admin_name => $config ) {
            $list   = $config[0] ? $config[1] : array( $config[1] ) ;
            $properties = array() ;
            foreach($list as $o ) {
                list($child_property, $my_proprty) = $o ;
                if( !$my_proprty ) {
                    $_anonymous_children[ $child_admin_name ][] = $child_property ;
                }
            }
        }
        
        $this->_anonymous_children  = $_anonymous_children ;
    }
    
    
    public function compile(){
        
        $class  = $this->admin->getCompileClass() ;
        
        if( $this->route_root ) {
            $class->addProperty('_admin_route_root',  $this->route_root ) ;
        }
        
        if( !empty($this->_route_children) ) {
            $class->addProperty('_admin_route_children',  $this->_route_children ) ;
            /*
            \Dev::debug($this->admin->name, 'children');
            \Dev::dump( $this->_route_children);
             */
        }
        
        if( !empty($this->_route_parents) ) {
            $parents    = array() ;
            
            foreach($this->admin->generator->admin_deep_order as $parent_name ) {
                if( !isset($this->_route_parents[$parent_name]) ) {
                    continue;
                }
                $o  = $this->_route_parents[$parent_name] ;
                if( count($o) > 1 ) {
                    foreach($o as $c) {
                        $child_property = $c[0] ;
                        $parent_property = $c[1] ;
                        
                        // if( !$parent_property ) continue ;
                        if( !isset($parents[ $parent_name ]) ) {
                            $parents[ $parent_name ] = array(1, array() );
                        }
                        $parents[ $parent_name ][1] = array(
                            $parent_property ,
                            $child_property ,
                        );
                    }
                } else {
                    foreach($o as $c) {
                        $child_property = $c[0] ;
                        $parent_property = $c[1] ;
                        // if( !$parent_property ) continue ;
                        $parents[ $parent_name ] = array(0, array(
                            $parent_property ,
                            $child_property ,
                        ) );
                    }
                }
            }
            /*
            \Dev::debug($this->admin->name, 'parents');
            \Dev::dump($parents, 8);
            */
            $class->addProperty('_admin_route_parents',  $parents ) ;
        }
        
    }
    
    public function hasChildAdmin( $admin_name , array & $visited ) {
        if( isset($visited[ $this->admin->name ]) ) {
            return false ;
        }
        $visited[ $this->admin->name ] = true ;
        
        if( isset($this->_children[$admin_name] ) ) {
            return true ;
        }
        foreach($this->_children as  $child_name => $none ) {
            $child  = $this->admin->generator->getAdminByName( $child_name ) ;
            if( $child->_route_assoc->hasChildAdmin( $admin_name, $visited) ) {
                return true ;
            }
        } 
        return false ;
    }
    
    public function hasChildPath($admin_name, array & $path, array & $visited ) {
        if( isset($visited[ $this->admin->name ]) ) {
            return false ;
        }
        
        $visited[ $this->admin->name ] = true ;
        
        $last_path_index    = count( $path ) ;
        array_push($path, null ) ;
        
        foreach($this->_children as  $child_name => $none ) {
            if( $none[0] ) {
                throw new \Exception("unimplement") ;
            }
            
            $path[$last_path_index] = $child_name ;
            if( $child_name === $admin_name ) {
                return true ;
            }
            
            $child  = $this->admin->generator->getAdminByName( $child_name ) ;
            if( $child->_route_assoc->hasChildPath( $admin_name, $path, $visited) ) {
                return true ;
            }
        }
        array_pop($path) ;
        return false ;
    }
    
    public function addRouteChildren( $my_property, $child_name, $child_property ) {
        if( $this->route_initialized  ) {
            throw new \Exception('big error') ;
        }
        
        if( !$this->admin->reflection->hasProperty( $my_property ) ) {
            if( !is_numeric($my_property) ) {
                throw new \Exception(sprintf("add admin child(%s) for not exists property(%s->%s)", $child_name, $this->admin->class_name, $my_property)) ;
            }
            $my_property    = null ;
        }
        
        // throw new \Exception(sprintf("add admin child(%s) for admin(%s) has duplicate property(%s)", $child_name, $this->admin->class_name,  $my_property)) ;
        
        if( $my_property ) {
            if( isset($this->_named_children[$my_property]) ) {
                list($_child_name , $_child_property) = $this->_named_children[$my_property] ;
                if( $_child_name !== $child_name || $_child_property !== $child_property ) {
                    throw new \Exception("big error");
                }
            }
            $this->_named_children[$my_property] = array($child_name , $child_property) ;
        } else {
            $this->_anonymous_children[] = array($child_name , $child_property );
        }
        
        if( !isset($this->_children[$child_name] ) ) {
            $this->_children[$child_name] = true ;
        }
        
    }
    
}
