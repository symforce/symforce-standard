<?php

namespace App\AdminBundle\Compiler\Cache ;

trait AdminPage {
    
    public $property_page_name;
    public $page_one2one_map;
    public $page_parent_entity;
    public $page_title_property;
    public $page_description_property;
    public $page_keywords_property;
    public $page_route_parent;

    /**
     * @var \AppAdminCache\AppPage\AdminAppPage 
     */
    protected $page_admin ;
    
    /**
     * @var array
     */
    protected $page_children ;
    
    public function fixPageEntityId($object, \App\AdminBundle\Entity\Page $page ){
        if( !$this->page_one2one_map || $this->page_parent_entity ) {
            throw new \Exception("big error!") ;
        }
        $id = $this->getReflectionProperty( $this->property_id_name )->getValue( $object ) ;
        if( !$id ) {
            // add call back after save
        }
    }
    
    /**
     * 
     * @return \AppAdminCache\AppPage\AdminAppPage
     */
    public function getPageAdmin() {
        if( !$this->property_page_name ) {
            return null ;
        }
        if( null === $this->page_admin ) {
            $this->page_admin   = $this->admin_loader->getAdminByClass( \App\AdminBundle\Compiler\MetaType\Admin\Page::PAGE_ENTITY_CLASS ) ;
        }
        return $this->page_admin ;
    }
    
    public function getRootPageObject($object = null, \App\AdminBundle\Entity\Page $object_page = null ) {
        $page_admin     = $this->getPageAdmin() ;
        $root_page  = null ;
        if( $object ) {
            if( !$object_page ) {
                $object_page   = $this->getPageObject( $object ) ;
            }
            $root_page  = $page_admin->getReflectionProperty( $page_admin->tree['parent'] )->getValue( $object_page ) ;
        } else if( $object_page ) {
            throw new \Exception("big error, need object with object page");
        }
        
        if( $this->page_parent_entity ) {
            $parent_admin   = $this->admin_loader->getAdminByClass($this->page_parent_entity) ;
            if( $this->route_parent !== $parent_admin ) {
                throw new \Exception("big error, page parent admin should be the route admin");
            }
            $parent_object  = null ;
            if( $object ) {
                $parent_object  = $this->getReflectionProperty( $this->property_page_name )->getValue( $object ) ; 
            } else {
                $parent_object  = $parent_admin->getObject() ;
                if( !$parent_object ) {
                    throw new \Exception("big error, page parent admin should have object") ;
                }
            }
            
            if( !$root_page ) {
                $root_page  = $parent_admin->getPageObject( $parent_object ) ;
            } else {
                // fix|check root page 
            }
            
        } else {
            if( !$root_page ) {
                $root_page  = $page_admin->getRepository()->findOneBy( array(
                        'admin_is_root' => true ,
                        'admin_entity_id'   => 0 ,
                        'admin_class'   => $this->class_name ,
                        'admin_page_property'   => $this->property_page_name ,
                ));
            }
            if( !$root_page ) {
                $root_page  = $page_admin->newObject() ;
                $root_page->admin_class = $this->class_name ;
                $root_page->admin_page_property = $this->property_page_name ;
            }
            $root_page->admin_is_root    = true ;
            $root_page->admin_entity_id  = 0 ;
            
            if( $this->page_route_parent ) {
                // $root_page->admin_route_parent = $this->page_route_parent ;
            }
        }
        
        if( null === $root_page->title ) {
            $root_page->title   = $this->getLabel() ;
        }
        
        if( $object_page ) {
            $page_admin->getReflectionProperty( $page_admin->tree['parent'] )->setValue( $object_page,  $root_page ) ;
        }
        
        return $root_page ;
    }
    
    public function getPageObject( $object ) {
        if( !$this->property_page_name ) {
            return null ;
        }
        $page   = null ;
        if( $this->page_one2one_map ) {
            $page   = $this->getReflectionProperty($this->property_page_name)->getValue( $object ) ;
        } else if( $this->page_parent_entity ) {
            // maybe fix in future
        }
        $admin  = $this->getPageAdmin() ;
        if( !$page ) {
            $page   = $admin->newObject() ;
            $this->getReflectionProperty($this->property_page_name)->setValue( $object , $page ) ;
        }
        if( $this->page_title_property ) {
            $admin->getReflectionProperty('title')->setValue( $page, $this->getReflectionProperty($this->page_title_property)->getValue( $object ) ) ;
        }
        if( $this->page_keywords_property ) {
            $admin->getReflectionProperty('meta_keywords')->setValue( $page, $this->getReflectionProperty($this->page_keywords_property)->getValue( $object ) ) ;
        }
        if( $this->page_description_property ) {
            $admin->getReflectionProperty('meta_description')->setValue( $page, $this->getReflectionProperty($this->page_description_property)->getValue( $object ) ) ;
        }
        if( $this->property_slug_name ) {
            $admin->getReflectionProperty('slug')->setValue( $page, $this->getReflectionProperty($this->property_slug_name)->getValue( $object ) ) ;
        }
        $page->admin_class  = $this->class_name ;
        $page->admin_page_property  = $this->property_page_name ;
        $page->admin_is_root    = false ;
        $page->admin_entity_id  = $this->getReflectionProperty( $this->property_id_name )->getValue( $object ) ;
       
        if( !$page->admin_entity_id ) {
            $page->admin_entity_id  = 0 ;
            $this->addEvent('flushed', function($_object, $_this) use ($page, $object, $admin ) {
                if( $_object !== $object ) {
                    throw new \Exception("error");
                }
                $this->removeEvent('flushed') ;
                $page->admin_entity_id  = $this->getReflectionProperty( $this->property_id_name )->getValue( $object ) ;
                $admin->update( $page ) ;
            });
        }
        
        if( !$this->page_children ) {
            $admin->getReflectionProperty( $admin->tree['leaf'] )->setValue( $page, 1 ) ;
        } else {
            $admin->getReflectionProperty( $admin->tree['leaf'] )->setValue( $page, 0 ) ;
        }
        
        $page_parent   = $this->getRootPageObject( $object, $page ) ;
        
        return $page ;
    }
    
}