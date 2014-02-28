<?php


namespace App\AdminBundle\Compiler\MetaType\Admin ;

use App\AdminBundle\Compiler\Annotation;

use App\AdminBundle\Compiler\Generator;

use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManager;
use Doctrine\Bundle\DoctrineBundle\Registry ;

class Entity extends \App\AdminBundle\Compiler\MetaType\Type {
    
    const USED_NAME =  ' app tmp temp put get post file save admin loader root parent child children tree id list create update delete view action batch action property filter search cache options accessor controller request object_id _app_admin_route_parameters _app_admin_route_parents ';
    
    const ANNOT_TREE_CLASS   = 'Gedmo\Mapping\Annotation\Tree' ;
    const ANNOT_TREE_LEAF_CLASS   = 'App\AdminBundle\Compiler\Annotation\TreeLeaf' ;
    const ANNOT_SLUG_CLASS   = 'Gedmo\Mapping\Annotation\Slug' ;
    const ANNOT_TOSTR_CLASS   = 'App\AdminBundle\Compiler\Annotation\ToString' ;
    const ANNOT_WORKFLOW_CLASS   = 'App\AdminBundle\Compiler\Annotation\Workflow' ;
    
    /**
     * @var string 
     */
    public $name ;
    
    /**
     * @var string 
     */
    public $property_id_name ;
    
    /**
     * @var string 
     */
    public $property_value_name ;
    
    /**
     * @var string 
     */
    public $property_slug_name ;
    
    /**
     * @var bool
     */
    public $property_slug_unique ;
    
    /**
     * @var bool
     */
    public $property_slug_nullable ;
    
    /**
     * @var string 
     */
    public $property_orm_map ;
    
    /**
     * @var string 
     */
    public $class_name ;
    
    /**
     * @var string 
     */
    public $parent_class_name = 'App\AdminBundle\Compiler\Cache\AdminCache' ;
    
    /**
     * @var string
     */
    public $_compile_class_name ;
    
    /**
     * @var \ReflectionClass 
     */
    public $reflection ;
    
    /**
     * @var \Doctrine\ORM\Mapping\ClassMetadata 
     */
    public $orm_metadata ;
    
    /**
     * @var string 
     */
    public $icon ;
    
    /**
     * @var \App\AdminBundle\Compiler\Generator\TransGeneratorValue 
     */
    public $label ;
    
    /**
     * @var Menu
     */
    public $menu ;
    
    /**
     * @var Dashboard
     */
    public $dashboard ;
    
    /**
     * @var string 
     */
    public $bundle_name ;

    /**
     * @var string 
     */
    public $tr_domain ;
    
    /**
     * @var string 
     */
    public $app_domain ;


    /** @var Form */
    public $form ;
    
    /** @var Page */
    public $page ;
    
    /** @var Owner */
    public $owner ;
    
    /** 
     * @var ActionCollection
     */
    public $action_collection ;
    
    /** 
     * @var Workflow
     */
    public $workflow ;


    /**
     * @var \App\AdminBundle\Compiler\Generator
     */
    public $generator ;
    
    /**
     * @var \App\AdminBundle\Compiler\Generator\TransGeneratorNode 
     */
    public $tr_node ;
    
    /**
     * @var \App\AdminBundle\Compiler\Generator\AnnotationCache 
     */
    public $cache ;
    
    /**
     * @var RouteAssoc
     */
    public $_route_assoc ;
    
    /**
     * @var integer 
     */
    public $position ;

    /**
     * @var array 
     */
    public $tree ;
    
    public $groups ;
    
    private $form_initialized ;
    
    public $template  ;
    public $_template_path ;
    public $_final_template  ;
    
    public $_orm_map ;
    public $_auth_parents = array() ;

    public function __construct(\App\AdminBundle\Compiler\Generator\AnnotationCache $cache, $bundle_name, ClassMetadata $meta , Generator $gen ) {
        
        $this->cache    = $cache ;
        
        $this->bundle_name = $bundle_name ;
        $this->class_name = $cache->class_name ;
        $this->reflection = $meta->getReflectionClass() ;
               
        $this->generator  = $gen ;
        $this->orm_metadata  = $meta ;
        
        if( $meta->isIdentifierComposite ) {
            // @TODO add Composite route handle
        } else {
            $this->property_id_name = $meta->getSingleIdentifierFieldName() ;
        }
        
        $this->setMyPropertie( $cache->class_annotations['App\AdminBundle\Compiler\Annotation\Entity'] ) ;
        
        if( ! $this->name ) {
            $this->name = strtolower( preg_replace( '/\W/', '_',  $this->class_name ) ) ;
        }
        $this->template = $this->name . '.admin.html.twig' ;
        $this->_template_path   =  $this->generator->getParameter('kernel.root_dir') . '/Resources/views/' . $this->template ;
        
        if( null === $this->_final_template ) {
            $tempalte = $this->bundle_name . ':' . basename(str_replace('\\', '\/', $this->class_name  )) . ':admin.macro.html.twig' ;
            try{
                $this->generator->loadTwigTemplatePath($tempalte) ;
                $this->_final_template  = $tempalte ;
            } catch( \InvalidArgumentException $e) {
                
            }
        }
        
        $compile_class_name   = ucfirst( $this->camelize( $this->name ) ) ;
        $this->_compile_class_name = 'AppAdminCache\\' . $compile_class_name . '\\Admin' . $compile_class_name ;

        if( null === $this->tr_domain ) {
             $this->tr_domain    = $this->bundle_name ;
        }
        $this->app_domain   = $gen->getAppDomain() ;
        
        if( isset($cache->class_annotations[self::ANNOT_TREE_CLASS]) ) {
            // not work for yml/xml,  because the private stof_doctrine_extensions.listener.tree service 
            $tree_annot_len = strlen( self::ANNOT_TREE_CLASS ) ;
            $this->tree = array() ;
            foreach( $cache->propertie_annotations as $property_name => $as ) {
                foreach($as as $annot_class => & $value ) {
                    if( 0 === strpos($annot_class, self::ANNOT_TREE_CLASS) ) {
                        $tree_config_key   = strtolower( substr( $annot_class, $tree_annot_len ) ) ;
                        $this->tree[ $tree_config_key ] = $property_name ;
                    }
                }
            }
            if( !isset($this->tree['parent']) ) {
                $this->throwError("missing @%sParent", self::ANNOT_TREE_CLASS );
            }
            if( !isset($this->tree['root']) ) {
                $this->throwError("missing @%sRoot", self::ANNOT_TREE_CLASS );
            }
        }
        
        if( isset( $cache->class_annotations[self::ANNOT_TOSTR_CLASS] ) ) {
            \Dev::dump( $cache->class_annotations[self::ANNOT_TOSTR_CLASS] ); exit ;
        }
        if( isset( $cache->class_annotations[self::ANNOT_TREE_LEAF_CLASS] ) ) {
            \Dev::dump( $cache->class_annotations[self::ANNOT_TREE_LEAF_CLASS] ); exit ;
        }
        
        foreach($cache->propertie_annotations as $property_name => $as ) {
            if( isset( $as[ self::ANNOT_SLUG_CLASS ] ) ) {
                if( $this->property_slug_name  ) {
                     $this->throwError("slug property duplicate(%s, %s)", $this->property_slug_name, $property_name );
                }
                $this->property_slug_name       = $property_name ;
                $this->property_slug_unique     = $meta->isUniqueField($property_name) ;
                $this->property_slug_nullable   = $meta->isNullable($property_name) ;
            }
            if( isset( $as[ self::ANNOT_TOSTR_CLASS ] ) ) {
                if( $this->property_value_name ) {
                    $this->throwError("@ToString(%s) is conflict with @ToString(%s)", $property_name , $this->property_value_name );
                }
                $this->property_value_name = $property_name ;
            }
            if( isset($as[self::ANNOT_TREE_LEAF_CLASS]) ) {
                if( !$this->tree ) {
                    $this->throwError("use @%s(%s) without @%s", self::ANNOT_TREE_LEAF_CLASS, $property_name, self::ANNOT_TREE_CLASS );
                }
                if( isset($this->tree['leaf']) ) {
                    $this->throwError("@ToString(%s) is conflict with %s", self::ANNOT_TREE_LEAF_CLASS, $property_name , $this->tree['leaf'] );
                }
                $this->tree['leaf'] = $property_name ;
            }
            if( isset($as[ Page::PAGE_ANNOT_CLASS ]) ) {
                if( $this->page ) {
                    $this->throwError("page property duplicate(%s, %s)", $this->page->pege_property, $property_name );
                }
                $this->page     = new Page($this, $property_name, $as[Page::PAGE_ANNOT_CLASS ] ) ;
            }
            
            if( isset($as[ Owner::OWNER_ANNOT_CLASS ]) ) {
                if( $this->owner ) {
                    $this->throwError("owner property duplicate(%s, %s)", $this->owner->owner_property, $property_name );
                }
                $this->owner     = new Owner($this, $property_name, $as[ Owner::OWNER_ANNOT_CLASS ] ) ;
            }
        }
        
        if( isset( $cache->class_annotations[self::ANNOT_WORKFLOW_CLASS] ) ) {
            $this->workflow   = new Workflow($this, $cache->class_annotations[self::ANNOT_WORKFLOW_CLASS] );
        }
        
        if( !$this->form ) {
            $this->form    = new Form($this) ;
        }
        
        $this->form->bootstrap() ;
        
        $this->action_collection    = new ActionCollection($this) ;
        
        $this->_route_assoc = new RouteAssoc( $this );
        
        if( null !== $this->position ) {
            if( $this->dashboard && null === $this->dashboard->position ) {
                $this->dashboard->position  = $this->position ;
            }
            if( $this->menu && null === $this->menu->position ) {
                $this->menu->position  = $this->position ;
            }
        }
    }
    
    private $lazy_initialized ;
    public function lazyInitialize() {
        if( $this->lazy_initialized  ) {
            throw new \Exception('big error') ;
        }
        $this->lazy_initialized = true ;
        
        $orm_map    = array() ;
        foreach($this->reflection->getProperties() as $p ) if( $p instanceof \ReflectionProperty ){
            $map = $this->getPropertyDoctrineAssociationMapping($p->name);
            if ($map) {
                $target_class   = $map['targetEntity'] ;
                $target_admin   = null ;
                $target_admin_name   = null ;
                if( $this->generator->hasAdminClass($target_class) ) {
                    $target_admin   =  $this->generator->getAdminByClass( $target_class ) ;
                    $target_admin_name   = $target_admin->name ;
                }
                $orm_map[$p->name] =  array( $target_class, $target_admin_name, $map['type'] ) ;
            }
        }
        $this->_orm_map  = $orm_map ;
        
        $this->_route_assoc->lazyInitialize() ; 
        
        $this->getLabel() ;
        
        if( $this->owner  ) {
            $this->owner->lazyInitialize() ;
        }
        
        if( $this->page ) {
            $this->page->lazyInitialize() ;
        }
        
        if( $this->workflow ) {
            // lazyInitialize before form ?
            $this->workflow->lazyInitialize() ; 
        }
        $this->form->lazyInitialize() ;
        $this->form_initialized = true ;
        $this->action_collection->lazyInitialize() ; 
        
    }
    
    public function childrenInitialize(){
        if( $this->workflow ) {
            $this->workflow->childrenInitialize() ;
        }
    }


    public function getLabel() {
        if( !$this->label || !($this->label instanceof \App\AdminBundle\Compiler\Generator\TransGeneratorValue) ) {
            if( !$this->label ) {
                $this->label = $this->generator->humanize( basename( str_replace('\\', '/', $this->class_name ) ) );
            }
            if( !$this->tr_node  ) {
                $this->tr_node  = $this->generator->getTransNodeByPath( $this->tr_domain , $this->name ) ;
            }
            $this->label = $this->tr_node->createValue('label', $this->label ) ;
        }
        return $this->label ;
    }
    
    protected function set_template( $value ) {
        $this->_final_template   = $value ;
    }

    public function getTemplate() {
          if( $this->_final_template ) {
              return $this->_final_template ;
          }
          return $this->template ;
    }

    protected function set_position( $position ) {
        if( preg_match('/\D/', $position ) ) {
            $this->throwPropertyError('position', "invalid value `%s`", $position ) ;
        }
        if( $position < 1 || $position > 0xff ) {
            $this->throwPropertyError('position', "invalid value `%s`", $position ) ;
        }
        $this->position= $position ;
    }
    
    protected function set_form( $value ) {
        $this->form  = new \App\AdminBundle\Compiler\MetaType\Form\Form($this, $value ) ;
    }

    protected function set_string( $value ){
        if( !property_exists( $this->class_name, $value) ) {
            $this->throwError("string:`%s` must be a valid property", $value );
        }
        if( $this->property_value_name && $this->property_value_name !== $value ) {
            $this->throwError("string:`%s` is conflict with @ToString(%s)", $value , $this->property_value_name );
        }
        $this->property_value_name  = $value ;
    }

    protected function set_name( $name ) {
        if( $this->isFreeze() ) {
            $this->throwError("can not set name after inited");
        }
        if( $this->name ) {
            $this->throwError("can not set name agian");
        }
        $name   = trim( strtolower( $name ) ) ;
       
        if( preg_match('/\s'  . preg_quote($name) . '\s/',  self::USED_NAME ) ) {
            $this->throwError("name can not set to `%s`", $name);
        }
        if( preg_match('/\W/', $name) ) {
            $this->throwPropertyError('name', "is invalid" ) ;
        }
        $this->name = $name ;
    }
    
    protected function set_class( $class_name ) {
        if( !class_exists($class_name) ) {
            $this->throwError( " %s not exists!", $class_name ) ;
        }
        $rc = new \ReflectionClass( $class_name ) ;
        if( !$rc->isSubclassOf( $this->parent_class_name ) ) {
            $this->throwError( " %s should extend form %s", $class_name, $this->parent_class_name ) ;
        }
        $this->parent_class_name   = $class_name ; 
    }


    protected function set_menu( $menu ) {
        $_menu  = new Menu() ;
        if( !is_array( $menu ) ) {
            $_menu->set_group( $menu ) ;
        } else {
            $_menu->setMyPropertie( $menu ) ;
        } 
        $this->menu = $_menu ;
    }
    
    protected function set_dashboard( $dashboard ) {
        $_dashboard  = new Dashboard() ;
        if( !is_array( $dashboard ) ) {
            $_dashboard->set_group( $dashboard ) ; 
        } else {
            $_dashboard->setMyPropertie( $dashboard ) ;
        }
        $this->dashboard = $_dashboard ;
    }
    
    /**
     * @return  string 
     */
    protected function set_tr_domain( $value ){
        if( !preg_match('/^\d+$/', $value) ) {
            $this->throwError("trnas_domain:`%s` is invalid", $value);
        }
        $this->tr_domain = $value  ;
    }
    
    public function isMappedProperty( $property ) {
        if( $this->orm_metadata ) {
            // @FIXME
            return true ;
        }
    }
    
    /**
     * @return string
     */
    public function getFilename() {
        return $this->reflection->getFileName() ; 
    }
    
    public function isFieldProperty( $property ) {
        if( $this->orm_metadata ) {
            return $this->orm_metadata->hasField( $property ) ;
        }
        return false ;
    }
    
    public function getPropertyDoctrineType( $property ){
         return $this->orm_metadata->getTypeOfField( $property ) ;
    }
    
    public function getPropertyDoctrineFieldMapping( $property ) {
         if( $this->orm_metadata->hasField( $property ) ) {
            return $this->orm_metadata->getFieldMapping( $property ) ;
         }
    }
    
    public function getPropertyDoctrineAssociationMapping( $property ) {
         if( $this->orm_metadata->hasAssociation( $property ) ) {
            return $this->orm_metadata->getAssociationMapping( $property ) ;
         }
    }
    
    private $_property_label    = array() ;
    public function getPropertyLabel( $property ) {
        if( !isset($this->_property_label[$property]) ) {
            if( !$this->form_initialized ) {
                throw new \Exception("bigger error!") ;
            }
            if( $this->form->children->hasProperty($property) ) {
                $label = $this->form->children->getProperty($property)->label ;
                // $this->debug("%s , %s, %s", $property , $label->getDomain(), $label->getPath() ) ;
            } else {
                $map    = $this->getPropertyDoctrineAssociationMapping( $property ) ;
                if( $map && $this->generator->hasAdminClass( $map['targetEntity'] ) ) {
                    $admin  = $this->generator->getAdminByClass( $map['targetEntity'] ) ;
                    $label     = $admin->getLabel() ;
                } else {
                    $label     = $this->generator->getTransValue( 'property', $property . '.label' ) ;
                }
                // $this->debug("%s , %s, %s", $property , $label->getDomain(), $label->getPath() ) ;
            }
            $this->_property_label[$property] = $label ;
        }
        return $this->_property_label[$property] ;
    }
    
    
    private $_property_twig_value   = array() ;
    public function getPropertyTwigValue( $property, $object ) {
        
        if( isset($this->_property_twig_value[$property]) ) {
            $access = $this->_property_twig_value[$property] ;
        } else {
            $access = null ;
            $get    = 'get' . $this->camelize( $property ) ;
            if( $this->reflection->hasMethod($get) ) {
                $method = $this->reflection->getMethod($get) ;
                if( $method->isPublic() && ! $method->isStatic() && !count($method->getParameters()) ) {
                    $access = '.' . $get . '()' ;
                }
            } else {
                if( $this->reflection->hasProperty($property) ) {
                    $prop    = $this->reflection->getProperty( $property ) ;
                    if( $prop->isPublic() ) {
                        $access = '.' . $property ;
                    } else {
                        $access = false ;
                    }
                } else {
                    throw new \Exception(sprintf("admin(%s) do not has property(%s->%s), and do not has methed(%s)", $this->name, $this->class_name, $property , $get ) );
                }
            }
            $this->_property_twig_value[$property]  = $access ;
        }
        
        if( false === $access ) {
            return sprintf('attribute(%s, "%s")', $object, $property ) ;
        }
        
        return sprintf('%s%s', $object, $access ) ;
    }
    
    public function throwError() {
        
        $msg   = call_user_func_array('sprintf', func_get_args() ) ;
        
        $_msg   = sprintf("%s, from annotation %s, class `%s` on file `%s` ", 
                $msg, 
                $this->getMeteTypeName(), $this->class_name , $this->getFileName() )  ; 
        throw new \Exception( $_msg );
    }
    
    /**
     * @var \App\AdminBundle\Compiler\Generator\PhpClass
     */
    private $_compile_class ;


    /**
     * @return \App\AdminBundle\Compiler\Generator\PhpClass
     */
    public function getCompileClass() {
        if( null === $this->_compile_class ) {
            $this->_compile_class   = $this->generator->getAdminPhpGenerator( $this ) ;
        }
        return  $this->_compile_class ;
    }
    
    public $_compile_validator_writer = null ;
    
    /**
     * @return \App\AdminBundle\Compiler\Generator\PhpWriter
     */
    public function getCompileValidatorWriter() {
        if( null === $this->_compile_validator_writer ) {
            $class  = $this->getCompileClass() ;
            $loadValidatorMetadata  = $class->addMethod('loadValidatorMetadata')
                ->setVisibility('public')
                ->addParameter(
                        \CG\Generator\PhpParameter::create('metadata')
                        ->setType( '\\Symfony\\Component\\Validator\\Mapping\\ClassMetadata' )
                    )
                ;
            $this->_compile_validator_writer    = $loadValidatorMetadata->getWriter() ;
        } 
        return $this->_compile_validator_writer ;
    }
    
    public function compile() {
        $class  = $this->getCompileClass() ; 
        
        $class->addProperty('name', $this->name ) ;
        $class->addProperty('bundle_name', $this->bundle_name ) ;
        
        $class->addProperty('property_id_name', $this->property_id_name ) ;
        $class->addProperty('property_value_name', $this->property_value_name ) ;
        $class->addProperty('property_slug_name', $this->property_slug_name ) ;
        $class->addProperty('property_slug_unique', $this->property_slug_unique ) ;
        $class->addProperty('property_slug_nullable', $this->property_slug_nullable ) ;
        
        $class->addProperty('class_name',  $this->class_name ); 
        $class->addProperty('tr_domain',  $this->tr_domain );
        $class->addProperty('app_domain',  $this->app_domain ) ;
        
        $class->addProperty('icon', $this->icon , 'string', null, 'public' ) ;
        $class->addProperty('template', $this->template , 'string', null, 'public' ) ;
        
        if( $this->tree ) {
            $class->addProperty('tree',  $this->tree , null, false, 'public' ) ;
        }
        
        if( $this->page ) {
            $this->page->compile() ;
        }
        
        if( $this->owner ) {
            $this->owner->compile() ;
        }
        
        $class->addProperty('orm_map', $this->_orm_map , 'string', null, 'public' ) ;
        
        $this->_route_assoc->compile() ;
        
        $this->form->compile() ;
        
        if( $this->workflow ) {
            // make sure workflow compile after form
            $this->workflow->compile() ; 
        }
        
        if( $this->_auth_parents ) {
            $class->addProperty('_auth_parents', $this->_auth_parents ) ;
        }
        
        $action_maps    = array() ;
        foreach( $this->action_collection->children as $action ) { 
            $_class  = $action->compile() ;
            if( $_class ) {
                $action_maps[ $action->name ] = $_class->getName() ;
            }
        }
        $class->addProperty('action_maps', $action_maps ) ;
    }
   
}
