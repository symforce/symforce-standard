<?php

namespace App\AdminBundle\Compiler\MetaType\Admin ; 

class Form extends EntityAware {
    
    const FORM_ANNOT_CLASS   = 'App\AdminBundle\Compiler\Annotation\Form' ;
    
    /**
     * @var \App\AdminBundle\Compiler\MetaType\PropertyContainer
     */
    public $children ;
    
    /**
     * @var array 
     */
    public $lazy_children = array() ;
    
    /**
     * @var array|App\AdminBundle\Compiler\MetaType\Form\Group 
     */
    public $groups  = array() ;
    
    public function __construct(Entity $entity, $annot = null ) {
        
        $this->setAdminObject($entity) ;
        
        if( $annot ) {
            $this->setMyPropertie( $annot ) ;
        }
        
        $this->children = new \App\AdminBundle\Compiler\MetaType\PropertyContainer($this) ;
        
    }
    
    public function bootstrap() {
        
        $entity = $this->admin_object ;
        
        if( $entity->groups ) {
            $this->set_groups( $entity->groups ) ;
        }
        
        if( isset($entity->cache->class_annotations[ self::FORM_ANNOT_CLASS ]) ) {
            foreach($entity->cache->class_annotations[ self::FORM_ANNOT_CLASS ]   as $property =>  $annot ) {
                if( !property_exists( $this->admin_object->class_name , $property) ){
                    $this->throwError(' property:%s is not exists', $property ) ;
                }
                $this->craeteElement($property, $annot );
            }
        }
        
        foreach($entity->cache->propertie_annotations as $property  => $as ) {
            
            if( isset($as[ self::FORM_ANNOT_CLASS ]) ) {
                $this->craeteElement($property, $as[ self::FORM_ANNOT_CLASS ] );
            } else {
                // if( $this->admin_object->tree ) \Dev::debug($property, $as );
            }
            
            if( isset($as['properties'][ self::FORM_ANNOT_CLASS ]) ) {
                $map        =  $this->admin_object->getPropertyDoctrineAssociationMapping( $property ) ;
                if( !$map ) {
                    $keys   = join(",", array_keys( $as['properties'][ self::FORM_ANNOT_CLASS ] ) ) ;
                    $this->throwPropertyError( $property, "use form with properties:[%s], but no orm map", $keys );
                }
                // @todo add check to make sure only work for one2one
                $parent_class   = $map['targetEntity'] ;
                
                // to_one 
                if( $map['type'] !== \Doctrine\ORM\Mapping\ClassMetadataInfo::ONE_TO_ONE ){
                    $keys   = join(",", array_keys( $as['properties'][ self::FORM_ANNOT_CLASS ] ) ) ;
                    $this->throwPropertyError( $property, "use form with properties:[%s], but not @ORM\OneToOne", $keys );
                }
                
                foreach($as['properties'][ self::FORM_ANNOT_CLASS ]  as $parent_property => $annot ) {
                    if( !property_exists($parent_class, $parent_property) ) {
                        $this->throwPropertyError( $property, "map entity:%s->%s property is not exists", $parent_class, $parent_property );
                    }
                    $this->lazy_children[$property][ $parent_class ][$parent_property] = $annot ;
                }
            }
        }
        /*
        if( $this->admin_object->tree ) {
            \Dev::dump($entity->cache, 4 );  exit;
        }
         */
    }


    private function craeteElement($property, \App\AdminBundle\Compiler\Annotation\Annotation $annot){
       $this->admin_object->generator->form_factory->create( $this->admin_object->class_name , $property , $annot, $this->children, $this->admin_object ) ; 
    }
    
    private $lazy_initialized ;

    public function lazyInitialize() {
        if( $this->lazy_initialized  ) {
            throw new \Exception('big error') ;
        }
        $this->lazy_initialized = true ;
        
        $this->tr_node  = $this->admin_object->tr_node->getNodeByPath('form') ;
        $this->children->tr_node    = $this->tr_node ;
        
        foreach ($this->lazy_children as $property => & $annotations ) {
            $embed  = null ;
            if( $this->children->hasProperty($property) ) {
                $embed  = $this->children->getProperty( $property ) ;
                if( 'appembed' !== $embed->compile_form_type ) {
                    $this->throwError("property:%s form type must be `embed',  you use:`%s`", $property, $embed->compile_form_type );
                }
            } else {
                $embed  = new \App\AdminBundle\Compiler\MetaType\Form\Embed( $this, $property) ;
            }
            
            foreach ($annotations as $parent_class => & $properties ) {
                if( null !== $embed->children  ) {
                    // , $embed->children->admin_object->class_name, $parent_class
                    $this->throwError("should map to one class for each property");
                }
                $admin  = $this->admin_object->generator->getAdminByClass( $parent_class ) ;
                $cache  = $this->admin_object->generator->getAnnotationCache( $parent_class ) ;
                $embed->children   = new \App\AdminBundle\Compiler\MetaType\PropertyContainer( $this, $admin ) ;
                foreach($properties as $parent_property => $annot ) {
                    $copy_annot = false ;
                    if( isset($cache->propertie_annotations[ $parent_property ][ self::FORM_ANNOT_CLASS ] ) ) {
                        $_annot = $cache->propertie_annotations[ $parent_property ][ self::FORM_ANNOT_CLASS ]  ;
                        
                        foreach($_annot as $key => $value ) {
                            if( null !==$value  &&  $value !== $annot->$key ) {
                                $annot->$key    = $value ;
                                $copy_annot = true ;
                            }
                        }
                        
                        if( 0 && $copy_annot ) {
                            throw new \Exception("not implement copy form yet!");
                            exit;
                        }
                        
                        $sub_element    = $this->admin_object->generator->form_factory->create( $parent_class, $parent_property, $annot, $embed->children, $admin ) ;
                      
                    } else {
                        $sub_element    = $this->admin_object->generator->form_factory->create( $parent_class, $parent_property, $annot, $embed->children, $admin ) ;
                    }
                }
            }
        }
        
        foreach ($this->children->properties as $property) {
            $property->lazyInitialize() ;
        }
        
        if( !isset($this->groups['default']) ) {
            $this->groups['default']    = new \App\AdminBundle\Compiler\MetaType\Form\Group( 'default' ) ;
        }
        
        foreach($this->children->properties as $field) {
            if( !$field->group ) {
                $this->groups['default']->add($field->class_property , $field->position);
            } else {
                 if( !isset( $this->groups[$field->group] ) ) {
                     $this->groups[$field->group]   = new \App\AdminBundle\Compiler\MetaType\Form\Group( $field->group ) ;
                 }
                $this->groups[$field->group]->add($field->class_property, $field->position);
            }
        } 
        
        foreach($this->groups as $group) {
            $group->fixLabel( $this->tr_node , $this->admin_object->generator->app_domain ) ;
            $group->sort() ;
        }
        
    }
    
    public function set_groups( $groups ) {
        
        if( !is_array( $groups )  ) {
            $this->throwError("@form.groups, must be array, not (type=%s, value=%s)", gettype($groups), var_export($groups) ) ;
        }
        

        foreach($groups as $id => $group ) {
            if( !is_array($group) ) {
                if( is_numeric($id) ) {
                    $group  = array(
                        'id'  => $group , 
                    );
                } else {
                    $group  = array(
                        'id'  => $id , 
                        'name'  => $group ,
                    );
                }
            } else {
                if( !isset($group['id']) ) {
                    if( !is_numeric($id) ) {
                        $group['id']    = $id ;
                    }
                }
            }
            $_group = new \App\AdminBundle\Compiler\MetaType\Form\Group( $group ) ;
            if( !$_group->id ) {
                $this->throwError("@form.groups, null id(%s)", $_group->id ) ;
            }
            if( isset($this->groups[ $_group->id ] ) ) {
                $this->throwError("@form.groups, duplicate id(%s)", $_group->id ) ;
            }
            $this->groups[ $_group->id ] = $_group  ;
        }

    }
    
    
    
    protected $_compile_form_writer = null ;

    /**
     * @return \App\AdminBundle\Compiler\Generator\PhpWriter
     */
    public function getCompileFormWriter() {
        if( null !== $this->_compile_form_writer ) {
            return $this->_compile_form_writer ;
        }
        $class  = $this->admin_object->getCompileClass() ;
        
        $method = $class
                ->addMethod( 'getFormBuilderOption' )
                ->setVisibility('public')
                ->addParameter(
                        \CG\Generator\PhpParameter::create('property')
                    )
                ->addParameter(
                        \CG\Generator\PhpParameter::create('action')
                        ->setType('\App\AdminBundle\Compiler\Cache\ActionCache')
                        ->setDefaultValue(null)
                    )
                ->addParameter(
                        \CG\Generator\PhpParameter::create('object')
                        ->setDefaultValue(null)
                    )
        ;
        $this->_compile_form_writer = $method->getWriter() ;
        
        
        $this->_compile_form_writer
                    ->writeln('$options = parent::getFormBuilderOption($property, $action, $object);')
                    ->writeln('if ( null !== $options ) return $options;')
                    ->write("\n")
                ;
        
        if( isset($this->admin_object->orm_metadata->table['uniqueConstraints']) ) {
            $uniqueConstraints  = $this->admin_object->orm_metadata->table['uniqueConstraints'] ;
            $validator_writer = null  ; 
            foreach($uniqueConstraints as $index_key => $config ) {
                if( !isset($config['columns']) ) {
                    continue ;
                }
                $columns    = array() ;
                foreach($config['columns'] as $_column ) {
                    $use_unshift   = true ;
                    if( !$this->admin_object->orm_metadata->hasField($_column) 
                            && ! $this->admin_object->orm_metadata->hasAssociation($_column)
                    ) {
                        $_column = preg_replace('/\_id$/', '', $_column) ;
                        if( !$this->admin_object->orm_metadata->hasAssociation($_column) ) {
                            throw new \Exception(sprintf("big error")) ;
                        }
                        $map = $this->admin_object->orm_metadata->getAssociationMapping($_column)  ;
                        if( $map['targetEntity'] == Owner::USER_ENTITY_CLASS ) {
                            $use_unshift = false ; 
                        }
                    }
                    $_column    =  var_export($_column, 1)  ;
                    if( $use_unshift ) {
                        array_unshift($columns, $_column);
                    } else {
                        array_push($columns, $_column);
                    }
                }
                // get last property uniue information ?
                $code   = sprintf('new \Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity(array( "fields" => array(%s), ',  join(',', $columns ) ) ;
                $code   .= '))' ;
                if( !$validator_writer ) {
                    $validator_writer     = $this->admin_object->getCompileValidatorWriter() ;
                }
                $validator_writer->writeln(' $metadata->addConstraint(' .  $code . ');');
            }
        }
        
        return $this->_compile_form_writer ;
    }
    
    /**
     * @return \App\AdminBundle\Compiler\Generator\PhpWriter
     */
    protected $_compile_action_form_writer = null ;

    /**
     * @return \App\AdminBundle\Compiler\Generator\PhpWriter
     */
    public function getCompileActionFormWriter() {
        if( null !== $this->_compile_action_form_writer ) {
            return $this->_compile_action_form_writer ;
        }
        $class  = $this->admin_object->getCompileClass() ;
        
        $fn =  'buildActionFormElement' ;
        
        $method = $class
                ->addMethod( $fn )
                ->setVisibility('public')
                ->addParameter(
                        \CG\Generator\PhpParameter::create('controller')
                        ->setType('\Symfony\Bundle\FrameworkBundle\Controller\Controller')
                    )
                ->addParameter(
                        \CG\Generator\PhpParameter::create('builder')
                        ->setType('\Symfony\Component\Form\FormBuilder')
                    )
                ->addParameter(
                        \CG\Generator\PhpParameter::create('admin')
                        ->setType('\App\AdminBundle\Compiler\Cache\AdminCache')
                    )
                ->addParameter(
                        \CG\Generator\PhpParameter::create('action')
                        ->setType('\App\AdminBundle\Compiler\Cache\ActionCache')
                    )
                ->addParameter(
                        \CG\Generator\PhpParameter::create('object')
                    )
                ->addParameter(
                        \CG\Generator\PhpParameter::create('property_name')
                    )
                ->addParameter(
                        \CG\Generator\PhpParameter::create('parent_property')
                    )
                ->addParameter(
                        \CG\Generator\PhpParameter::create('options')
                        ->setType('array')
                        ->setPassedByReference(true)
                    )
        ;
        
        $this->_compile_action_form_writer = $method->getWriter() ;
        return $this->_compile_action_form_writer ;
    }
    
    public function compile() {
        
        $auth_properties = array() ;
        
        foreach($this->children->properties as $child) {
            $child->compileForm() ;
            if(  $child->auth_node ) {
                $auth_properties[ $child->class_property ] = true ;
                continue ;
            }
        }
        
        $class  = $this->admin_object->getCompileClass() ;
        if( !empty($auth_properties) ) {
            $class->addProperty('auth_properties', array_keys($auth_properties), 'array', null, 'public' ) ;
        }
    }
    
}