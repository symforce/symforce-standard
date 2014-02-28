<?php

namespace App\AdminBundle\Compiler\Cache ;

trait AdminRoute {
    
    /**
     * @var int
     */
    protected $route_object_id ;
    
    /** @var array */
    protected $action_objects = array() ;

    /**
     * @var bool
     */
    protected $_admin_route_root ;
    
    /**
     * @var array
     */
    protected $_admin_route_children ;
    
    /**
     * @var array
     */
    protected $_admin_route_parents ;
    
    public function getRouteObjectId() {
        return $this->route_object_id  ;
    }
    
    public function setRouteObjectId( $id ) {
        $this->route_object_id  = $id ;
    }
    
    public function getRouteObject() {
        if( $this->route_object_id )  return $this->getObjectById( $this->route_object_id ) ;
    }
    
    protected $_route_parameters  = array() ;
    public function setRouteParameters(array & $parameters ) {
        $this->_route_parameters    = $parameters ;
    }
    public function getRouteParameters(){
        return $this->_route_parameters ;
    }


    public $_route_parents = array() ;
    public function setRouteParents(array & $parents ) {
        $this->_route_parents   = $parents ;
    }
    public function getRouteParents() {
        return $this->_route_parents ;
    }
    
    public function getRouteAction(){
        return $this->admin_loader->getRouteAction() ;
    }
    
    private $_is_route_admin ;
    public function isRouteAdmin() {
        if( null !== $this->_is_route_admin ) {
            return $this->_is_route_admin ;
        }
        $this->_is_route_admin   =  $this->name === $this->admin_loader->getRouteAdmin() ;
        return $this->_is_route_admin ;
    }
    
    protected $route_parent ;
    
    /**
     * @return AdminCache
     */
    public function getRouteParent() {
        $this->fixAdminRouteParent() ;
        return $this->route_parent ;
    }
    
    public function setRouteParent(AdminCache $parent = null ) { 
        if( null !== $parent ) {
            if( !$this->_admin_route_parents ) {
                throw new \Exception(sprintf("admin(%s) no parent, you set(%s)", $this->admin_name, $parent->getName() )) ;
            }
            if( !isset($this->_admin_route_parents[$parent->getName()]) ) {
                throw new \Exception(sprintf("admin(%s) no parent(%s)", $this->admin_name, $parent->getName() )) ;
            }
        }
        $this->route_parent = $parent ;
        $this->route_parent_property    = null ;
        $this->route_parent_path    = null ;
    }

    protected $route_parent_property ;
    public function setRouteParentProperty( $property = null ) {
        if( null !== $property  ) {
            if( !is_string($property) ) {
                throw new \Exception(sprintf("expect string, but get(%s)", is_object($property)?get_class($property): gettype($property) ));
            }
            if( !property_exists( $this->class_name, $property) ) {
                throw new \Exception(sprintf("property (%s->%s) not exists",  $this->class_name, $property ));
            }
            // @todo check ORM ?
        }
        $this->route_parent_property    = $property ;
    }
    
    public function getRouteParentProperty() {
         $this->fixAdminRouteParent() ;
        return $this->route_parent_property ;
    }
    
    public function hasChildAdmin($admin_name, array & $path, array & $visited ) {
        if( isset($visited[ $this->name ]) ) {
            return false ;
        }
        
        if( empty($this->_admin_route_children) ) {
            return ;
        }
        
        $visited[ $this->name ] = true ;
        
        $last_path_index    = count( $path ) ;
        array_push($path, array( null, null ) ) ;
        
        foreach($this->_admin_route_children as $child_name => $none ) {
            if( $none[0] ) {
                throw new \Exception("unimplement") ;
            }
            $path[$last_path_index][0]  = $child_name ;
            $path[$last_path_index][1]  = $none[1][0] ;
            if( $child_name === $admin_name ) {
                return true ;
            }
            $child  = $this->admin_loader->getAdminByName( $child_name ) ;
            if( $child->hasChildAdmin( $admin_name, $path, $visited) ) {
                return true ;
            }
        }
        array_pop($path) ;
        return false ;
    }

    private function fixAdminRouteParent() {
        if( !$this->route_parent ) {
            if( !$this->_admin_route_root ) {
                // try get current admin as my parent 
                $route_admin_name    = $this->admin_loader->getRouteAdmin() ;
                if( $route_admin_name !== $this->name ) {
                    if( ! $this->admin_loader->hasAdminName( $route_admin_name ) ) {
                            throw new \Exception(sprintf("route admin `%s` is not valid admin name", $route_admin_name ) );
                    }
                    $route_admin = $this->admin_loader->getAdminByName( $route_admin_name ) ; 
                    $current_route_parents  = $route_admin->getRouteParents() ;
                    $current_route_parents_path = array_reverse( array_keys($current_route_parents) ) ;
                    $current_route_parents[ $route_admin_name ] = $route_admin ;
                    array_unshift( $current_route_parents_path, $route_admin_name ) ;
                    
                    foreach($current_route_parents_path as $_parent_name ) {
                        if( $route_admin_name === $this->name ) {
                            throw new \Exception("big error, route use self as parent");
                        }
                        $route_admin    = $current_route_parents[ $_parent_name ];
                        $visted = array() ;
                        $route_admin_children = array() ;
                        if( $route_admin->hasChildAdmin($this->name, $route_admin_children, $visted ) ) {
                            $child_admin_parent = $route_admin ;
                            foreach($route_admin_children as $config ) {
                                $child_admin_name   = $config[0] ;
                                $child_admin_property   = $config[1] ;
                                $child_admin = $this->admin_loader->getAdminByName( $child_admin_name ) ;
                                $child_admin->setRouteParent( $child_admin_parent ) ;
                                $child_admin->setRouteParentProperty( $child_admin_property ) ;
                                /**
                                 * @fixme how to get the object Id ?
                                 */
                                
                                $child_admin_parent = $child_admin ;
                            }
                            break ;
                        }
                    }
                }
                
                if( !$this->route_parent ) {
                    if($this->_admin_route_parents) {
                       foreach($this->_admin_route_parents as $parent_admin_name => $config ) {
                            $list = $config[0] ? $config[1]: array( $config[1] ) ;
                            foreach($list as $o) {
                                $parent_property    = $o[0] ;
                                $my_property    = $o[1] ;
                                if( $parent_property ) {
                                    $parent    = $this->admin_loader->getAdminByName( $parent_admin_name ) ;
                                    $this->route_parent = $parent ;
                                    $this->route_parent_property    = $my_property ;
                                    break ;
                                }
                            }
                            if( $this->route_parent_property  ) {
                                break ;
                            }
                       } 
                    }
                }
                if( !$this->route_parent ) {
                    throw new \Exception(sprintf("try use admin `%s` without route parents, maybe current root admin `%s` is parent?", $this->name, $route_admin_name)) ;
                }
            }
        }
    }


    public function getAdminParentRoutePath( array & $path ){
        $this->fixAdminRouteParent() ;
        if( $this->route_parent ) {
            if( !is_string($this->route_parent_property ) ) {
                \Dev::dump( $this ) ;exit;
            }
            array_unshift($path, $this->route_parent->getName() . '.' .  $this->route_parent_property );
            $this->route_parent->getAdminParentRoutePath( $path );
        }
    }
    
    private $_route_children_admin ;

    protected function getRouteChildren(){
        if( null !== $this->_route_children_admin ) {
            return $this->_route_children_admin ;
        }
        $object = $this->getRouteObject() ;
        $children   = array() ;
        if( $object  && $this->_admin_route_children ) {
            foreach($this->_admin_route_children as $child_admin_name => $config ) {
                if( $config[0] ) {
                    throw new \Exception("unimplement multi child");
                } else {
                    list($child_property, $my_property) = $config[1] ;
                    $admin  = $this->admin_loader->getAdminByName( $child_admin_name ) ;
                    if( !$my_property ) {
                        if( $admin->getRouteParent() !== $this ) {
                            continue ;
                        }
                    }
                    
                    $admin->setRouteParent( $this );
                    $admin->setRouteParentProperty( $child_property );
                    $admin->setRouteParameters( $this->_route_parameters );
                    $children[] = $admin ;
                }
            }
        }
        $this->_route_children_admin    = $children ;
        return $children ;
    }
    
    public function getAdminRouteChildren(){
        return $this->_admin_route_children ;
    }
    
    protected function fixRouteObject($object){
        if( $this->_route_parents ) {
            $property   = $this->getReflectionProperty( $this->route_parent_property) ;
            $property->setValue( $object, $this->route_parent->getRouteObject() ) ;
        }
        if( $this->property_owner_name ) {
            $id = $this->getId($object) ;
            if( $id ) {
                $property   = $this->getReflectionProperty( $this->property_owner_name ) ;
                $user   = $property->getValue( $object );
                if( ! $user ) {
                    $property->setValue( $object, $this->admin_loader->getCurrentLoginUser() ) ;
                }
            }
        }
        if( $this->tree ) {
            if( $this->getTreeObjectId() ) {
               $this->getReflectionProperty( $this->tree['parent'] )->setValue($object, $this->getTreeObject() ) ;
            }
        }
    }
    
    public function path($action_name, $object = null, $options = array() ) { 
        return $this->getAction($action_name)->path( $object, $options ) ;
    }
    
    public function childpath($object, $property_name, $child_admin_name, $action_name, $child_collection = null, $options = array() ){
        
        if( !isset($this->_admin_route_children[$child_admin_name]) ) {
             throw new \Exception(sprintf("admin(%s) property:%s for child admin(%s)  not exists", $this->name, $property_name, $child_admin_name )) ;
        }
        $config = $this->_admin_route_children[$child_admin_name] ;
        
        if( $config[0] ) {
            throw new \Exception("unimplement multi child");
        }
        
        list($child_property, $my_property) = $config[1] ;
        if( $my_property !== $property_name ) {
            throw new \Exception("big error") ;
        }
        
        $admin  = $this->admin_loader->getAdminByName( $child_admin_name ) ;
        $admin->setRouteParent( $this );
        $admin->setRouteParentProperty( $child_property ) ;
        
        $object_key = $this->name . '_id' ;
        $options[ $object_key ] = $this->getId( $object ) ;
        
        $url    = $admin->path($action_name, null , $options ) ;
        
        return $url ;
    }
    
    
    public function countBy($property, $object ) {
        
        $em = $this->getManager() ;
        
        if( isset($this->orm_map[$property]) ) {
            if( !$this->admin_loader->hasAdminClass( $object ) ) {
                throw new \Exception("not implement") ;
            }
            $admin  = $this->admin_loader->getAdminByClass( $object ) ;
            $value  = $admin->getId( $object ) ;
            
        } else {
            if( is_object($object) || is_array($object) ) {
                throw new \Exception("big error") ;
            }
            $value  = $object ;
        }
        
        $dql   = sprintf("SELECT count(a.%s) FROM %s a WHERE a.%s=:value", $this->property_id_name, $this->class_name, $property );
        
        if( $this->route_parent && $this->route_parent_property !== $property ) {
            $parent_object  = $this->route_parent->getRouteObject() ;
            $dql    .= sprintf(' AND a.%s=%d', $this->route_parent_property , $this->route_parent->getId( $parent_object ) ) ;
        }
        
        $query  = $em->createQuery( $dql ) ;
        $query->setParameter('value', $value ) ; 
        
        return $query->getSingleScalarResult();
    }
    
    
    protected function getParentDqlWhere(array & $where , array & $list ) {
        
        foreach($list as $_parent_object) {
            $parent = $this->admin_loader->getAdminByClass($_parent_object) ;
            $my_property    = null ;
            $parent_property    = null ;
            if($this->_admin_route_parents) {
                foreach($this->_admin_route_parents as $parent_admin_name => $config ) {
                     if( $parent_admin_name !== $parent->name ) {
                         continue ;
                     }
                     $list = $config[0] ? $config[1]: array( $config[1] ) ;
                     foreach($list as $o) {
                         $parent_property    = $o[0] ;
                         $my_property    = $o[1] ;
                         break ;
                     }
                     break ;
                }
            }
            if( !$my_property ) {
                throw new \Exception(sprintf("admin(%s) no parent for admin(%s)", $this->name,  $parent->name ));
            }
            $where[ ]    = 'a.' . $my_property . '=' . $parent->getId($_parent_object) ;
        }
    }
    
    public function getListByParents( $parent_object , $count = false, array $where = array() ) {
        
        if( !empty($parent_object) ) {
            $list   = is_array($parent_object) ? $parent_object : array($parent_object) ;
            $this->getParentDqlWhere($where, $list ) ;
        } else if ( empty($where) ) {
            throw new \Exception("parent objects can not be empty");
        }
        
        $select = $count ? sprintf('count(a.%s)',  $this->property_id_name ) : 'a' ;
        $dql   = sprintf("SELECT %s FROM %s a WHERE %s", $select, $this->class_name, join(' AND ', $where ) );
        
        $em = $this->getManager() ;
        $query  = $em->createQuery( $dql ) ;
        if( $count ) {
            return $query->getSingleScalarResult() ;
        }
        
        return $query->getResult() ;
    }
    
    public function getCountByParentAdmin( $_parent_object ) {
        $where  = array() ;
        if( $this->route_parent && $this->route_parent !== $_parent_object ) {
            $parent_object  = $this->route_parent->getRouteObject() ;
            if( $parent_object ) {
                $where[]    = sprintf(' AND a.%s=%d', $this->route_parent_property , $this->route_parent->getId( $parent_object ) ) ;
            } else {
                /**
                 * @fixme , add check ?
                 */
                // throw new \Exception("big error?");
            }
        }
        return $this->getListByParents($_parent_object, true ) ;
    }
}