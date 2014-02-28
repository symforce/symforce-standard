<?php


namespace App\AdminBundle\Compiler\MetaType\Admin ;

use App\AdminBundle\Compiler\Annotation;
use App\AdminBundle\Compiler\Generator;

/**
 * Description of Page
 *
 * @author loong
 */
class Page extends EntityAware {
    
    const PAGE_ANNOT_CLASS   = 'App\AdminBundle\Compiler\Annotation\Page' ;
    const PAGE_ENTITY_CLASS  = 'App\AdminBundle\Entity\Page' ;
    const PAGE_CONTROLLER_CLASS = 'App\AdminBundle\Controller\PageController' ;
    
    public $parent_entity ;
    public $parent_admin ;
    
    public $route_parent ;
    
    public $route_path ;
    
    public $page_property ;
    public $page_one2one_map ;
    
    public $title_property ;
    public $description_property ;
    public $keywords_property ;
    
    public $controller ;
    
    public $children = array() ;
    public $children_one2one = false ;
    
    private $_compile_class_name ;
    
    public function __construct(Entity $admin, $property, Annotation\Page $annot ) {
        
        if( $admin->class_name === self::PAGE_ENTITY_CLASS ) {
            $this->throwError("can not set Page for entity:%s", self::PAGE_ENTITY_CLASS, $property);
        }
        
        $map    = $admin->getPropertyDoctrineAssociationMapping($property) ;
        if( !$map ) {
            if( $admin->orm_metadata->hasField( $property ) ) {
                $orm_type   = $admin->orm_metadata->orm_metadata->getTypeOfField( $property ) ;
                $this->throwError("property:%s can not use orm type(%s)", $property, $orm_type );
            } else {
                /**
                 * single page without OneToOne map , need set a parent id for this collection
                 */
            }
        } else {
            if( self::PAGE_ENTITY_CLASS === $map['targetEntity'] ) {
                /**
                 * single page with OneToOne map, need set a parent id for this collection
                 */
                if( \Doctrine\ORM\Mapping\ClassMetadataInfo::ONE_TO_ONE !== $map['type'] ) {
                    $this->throwError("property:%s map to %s must be One2One, you use %s", $property, self::PAGE_ENTITY_CLASS, $map['type'] );
                }
                if( self::PAGE_ENTITY_CLASS === $admin->class_name ) {
                    $this->throwError("property:%s can not use Page(%s) to it self",$property, self::PAGE_ENTITY_CLASS );
                }
                $this->page_one2one_map = true ;
            } else {
                $this->parent_entity    = $map['targetEntity'] ;
            }
        }
        
        $this->page_property  = $property ;
        
        $this->setAdminObject( $admin ) ;
        $this->setMyPropertie( $annot ) ;
        if( !$this->title_property  ) {
            if( $admin->property_value_name  ) {
                $this->title_property   = $admin->property_value_name ;
                
            } else {
                // $this->throwError("page must set a title property");
            }
            
        }
        if( !$this->controller ) {
            $controller    = preg_replace('/\\\\Entity\\\\(\w+)$/', '\\\\Controller\\\\\\1Controller', $admin->class_name ) ;
            if( class_exists($controller) ) {
                $this->set_controller( $controller ) ;
            }
        }
        
        $this->_compile_class_name = $admin->_compile_class_name . 'WebPageGenerator'  ; 
    }
    
    protected function set_parent($name) {
        if( $this->parent_entity ) {
             $this->throwError("property:%s already has parent:%s, can not set to :%s", $this->page_property, $this->parent_entity , $name );
        }
        $this->route_parent  = $name ;
    }

    protected function set_title( $value ) {
        if( ! property_exists( $this->admin_object->class_name , $value) ) {
            $this->throwError("title use property:%s not exists ", $value);
        }
        $this->title_property   = $value ;
    }
    
    protected function set_path( $value ) {
        if(preg_match('/\W/', $value) ) {
            $this->throwError("path:%s is not valid ", $value);
        }
        $this->route_path   = $value ;
    }
    
    protected function set_description($value){
        if( ! property_exists( $this->admin_object->class_name , $value) ) {
            $this->throwError("description use property:%s not exists ", $value);
        }
        $this->description_property   = $value ;
    }
    
    protected function set_keywords($value) {
        if( ! property_exists( $this->admin_object->class_name , $value) ) {
            $this->throwError("keywords use property:%s not exists ", $value);
        }
        $this->keywords_property    = $value ;
    }
    
    protected function set_controller($value) {
        if( !class_exists($value) ) {
            $this->throwError("controller:%s not exists ", $value);
        }
        if( !is_subclass_of($value, self::PAGE_CONTROLLER_CLASS) ) {
            $this->throwError("controller:%s is not subclass of %s", $value, self::PAGE_CONTROLLER_CLASS);
        }
        $this->controller    = $value ;
    }

    protected function addChild(Page $child ) {
        $this->children[ $child->admin_object->name ] = $child ;
        if( $child->page_one2one_map ) {
            $this->children_one2one = true ;
        }
    }

    public function lazyInitialize() {
        if( $this->parent_entity ) {
            $this->parent_admin  = $this->admin_object->generator->getAdminByClass( $this->parent_entity ) ;
            if( !$this->parent_admin->page ) {
                $this->throwError("property:%s parent admin(%s) do not set Page", $this->page_property, $this->parent_entity );
            }
            $this->parent_admin->page->addChild( $this ) ;
        }
    }
    
    /**
     * @return \App\AdminBundle\Compiler\Generator\PhpClass
     */
    public function getCompileClass() {
        if( null === $this->_compile_class ) {
            $class = new \App\AdminBundle\Compiler\Generator\PhpClass() ;
            $class
                ->setName( $this->_compile_class_name )
                ->setParentClassName( '\App\AdminBundle\Compiler\Generator\RouteWebPageGenerator' )
                ->setFinal(true)
                ; 
            $this->_compile_class  = $class ;
        }
        return  $this->_compile_class ;
    }
    
    private $_copy_properties ;
    public function getCopyProperties() {
        if( !$this->page_one2one_map ) {
            return array() ;
        }
        if( null !== $this->_copy_properties ) {
            return $this->_copy_properties ;
        }
        $copy_properties    = array() ;
        if( $this->title_property ) {
            $copy_properties[ $this->title_property ] = 'title' ;
        }
        if( $this->keywords_property ) {
            $copy_properties[ $this->keywords_property ] = 'keywords' ;
        }
        if( $this->description_property ) {
            $copy_properties[ $this->description_property ] = 'description' ;
        }
        if( $this->admin_object->property_slug_name ) {
            $copy_properties[ $this->admin_object->property_slug_name ] = 'slug' ;
        }
        $this->_copy_properties     = $copy_properties ;
        return $copy_properties ;
    }
    
    public function compile(){
        /*
        $page_admin = $this->admin_object->generator->getAdminByClass( self::PAGE_ENTITY_CLASS ) ;
         */
        
        $this->admin_object->generator->addLazyLoaderCache('web_page_class', $this->admin_object->class_name, '\\' . $this->_compile_class_name );
        
        $admin_options    =  array(
            'property_page_name' => $this->page_property ,
            'page_one2one_map' => $this->page_one2one_map ,
            'page_parent_entity' => $this->parent_entity ,
            
            'page_title_property' => $this->title_property ,
            'page_description_property' => $this->description_property ,
            'page_keywords_property' => $this->keywords_property ,
            'page_route_parent' => $this->route_parent ,
        ) ;
        $admin_class = $this->admin_object->getCompileClass() ;
        foreach($admin_options as $key => $value ) {
            $admin_class->addProperty($key, $value, null, false, 'public' ) ;
        }
        if( $this->page_one2one_map ) {
            $admin_class->addLazyArray( 'copy_properties',  $this->page_property , $this->getCopyProperties() ) ; 
        }
        
        $page_options    =  array(
            'property_page_name' => $this->page_property ,
            'page_one2one_map' => $this->page_one2one_map ,
            'page_parent_entity' => $this->parent_entity ,
            
            'admin_class' => $this->admin_object->class_name ,
            'admin_name' => $this->admin_object->name ,
            'route_path' => $this->route_path ,
            'controller' => $this->controller ,
        ) ;
        
        $class  = $this->getCompileClass() ; 
        foreach($page_options as $key => $value ) {
            $class->addProperty($key, $value) ;
        }
        
        $children   = array() ;
        foreach($this->children as $child_page){
            $children[] = $child_page->admin_object->name ;
        }
        
        if( count($children) ) {
            $admin_class->addProperty('page_children', $children) ;
            $class->addProperty('children', $children ) ;
        }
        
        $class->writeCache() ;
        
    }
    
}
