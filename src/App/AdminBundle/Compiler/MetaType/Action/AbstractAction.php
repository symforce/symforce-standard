<?php

namespace App\AdminBundle\Compiler\MetaType\Action ;

use App\AdminBundle\Compiler\Generator\ActionTwigGenerator ;

abstract class AbstractAction extends \App\AdminBundle\Compiler\MetaType\Admin\EntityAware {
    
    public function getRouteObject() {
        return $this->admin_object ; 
    }
    
    // ========= config properties
    public $icon ;
    
    public $dashboard ;
    public $table ;
    public $toolbar ;
    
    // ========= system properties
    public $name ;
    
    public $admin_name ;
    
    public $template = null ;
    public $final_template = null ;
    
    public $property_annotation_class_name = null ;
    
    public $_compile_class_name ;
    
    /**
     * @var \App\AdminBundle\Compiler\MetaType\PropertyContainer
     */
    public $children ;
    
    public $lazy_children = array() ;
    
    /**
     * @var ActionTwigGenerator 
     */
    public $_twig = null ;
    
    /**
     * @var string 
     */
    public $parent_class_name ;
    
    /**
     * @var \App\AdminBundle\Compiler\Generator\TransGeneratorValue 
     */
    public $action_label ;
    
    /**
     * @var \App\AdminBundle\Compiler\Generator\TransGeneratorValue 
     */
    public $title_label ;
    
    /**
     * @var \App\AdminBundle\Compiler\Generator\TransGeneratorValue 
     */
    public $form_label ;
    
    public function __construct($name, \App\AdminBundle\Compiler\MetaType\Admin\Entity $entity, \App\AdminBundle\Compiler\Annotation\Annotation $annot = null) {
        
        $this->setAdminObject($entity) ;
        if( $annot ) {
            $this->setMyPropertie( $annot ) ;
        }
        if( !$this->name  ) {
            $this->name     = $name ;
        }
        $this->admin_name   = $entity->name ;
        
        $this->_compile_class_name = $entity->_compile_class_name . 'Action' . ucfirst( $entity->generator->camelize( $this->name ) )  ; 
        
        if( null === $this->parent_class_name ) {
            if( $this->isCustomize() ) {
                throw new \Exception(sprintf("entity(%s) @Admin\Action(%s) missing class value", $entity->class_name, $name) ) ;
            }
            $this->parent_class_name = 'App\AdminBundle\Compiler\Cache\\' .  ucfirst( $entity->generator->camelize( $this->name ) ) . 'ActionCache' ;
        }
        
        if( null !== $this->property_annotation_class_name  ) {
            
            $this->children = new \App\AdminBundle\Compiler\MetaType\PropertyContainer($this) ;

            if( isset($entity->cache->class_annotations[ $this->property_annotation_class_name ]) ) {
                foreach($entity->cache->class_annotations[ $this->property_annotation_class_name ]   as $property =>  $annot ) {
                    if( !property_exists( $this->admin_object->class_name , $property) ){
                        $this->throwError(' property:%s is not exists', $property ) ;
                    }
                    // $property , $annot 
                    $this->addProperty($property, $annot ) ;
                }
            }

            foreach($entity->cache->propertie_annotations as $property  => & $as ) {
                if( isset($as[ $this->property_annotation_class_name ]) ) {
                    $annot  = $as[ $this->property_annotation_class_name ]  ;
                    // $property , $annot 
                    $this->addProperty($property, $annot ) ;
                }
                
                if( isset($as['properties'][ $this->property_annotation_class_name ]) ) {
                    $map        =  $this->admin_object->getPropertyDoctrineAssociationMapping( $property ) ;
                    if( !$map ) {
                        $keys   = join(",", array_keys( $as['properties'][ $this->property_annotation_class_name ] ) ) ;
                        $this->throwPropertyError( $property, "use form with properties:[%s], but no orm map", $keys );
                    }
                    // @todo add check to make sure only work for one2one
                    $target_class   = $map['targetEntity'] ;

                    foreach($as['properties'][ $this->property_annotation_class_name ]  as $target_property => $annot ) {
                        if( !property_exists($target_class, $target_property ) ) {
                            $this->throwPropertyError( $property, "map entity:%s->%s property is not exists", $target_class, $target_property );
                        }
                        $this->lazy_children[$property][$target_property] = $annot ;
                    }
                }

            }
        }
        
        
    }
    
    private $lazy_initialized ;

    public function lazyInitialize() {
        if( $this->lazy_initialized  ) {
            throw new \Exception('big error') ;
        }
        $this->lazy_initialized = true ;
        
        $this->tr_node  = $this->admin_object->tr_node->getNodeByPath( $this->name ) ;
        
        
        if( !$this->label ) {
            $this->label     = $this->admin_object->generator->getTransValue( 'action', $this->name . '.label' ) ;
        } else {
            $this->label     = $this->tr_node->createValue( 'label', $this->label ) ;
        }
        
        if( !$this->action_label ) {
            $this->action_label     = $this->admin_object->generator->getTransValue( 'action', $this->name . '.action_label' ) ;
        } else {
            $this->action_label     = $this->tr_node->getValue( 'action_label', $this->action_label ) ;
        }
        
        if( !$this->title_label ) {
            $this->title_label     = $this->admin_object->generator->getTransValue( 'action', $this->name . '.title_label' ) ;
        } else {
            $this->title_label     = $this->tr_node->getValue( 'action_label', $this->title_label ) ;
        }
        
        if( !$this->form_label ) {
            $this->form_label     = $this->admin_object->generator->getTransValue( 'action', $this->name . '.title_label' ) ;
        } else {
            $this->form_label     = $this->tr_node->getValue( 'action_label', $this->form_label ) ;
        }
        
        foreach ($this->lazy_children as $property => & $annotations ) {
            foreach ($annotations as $target_property => & $annot ) {
                $this->addParentProperty( $property, $target_property, $annot );
            }
        }
        
        if( $this->children  ) {
            $this->children->tr_node    = $this->tr_node ;
            foreach($this->children->properties as $property) {
                $property->lazyInitialize() ; 
            }
        }
    }
    
    public function addProperty( $property, \App\AdminBundle\Compiler\Annotation\Annotation $annot ){
        new BaseProperty($this->children , $this->admin_object, $property, $annot ) ;
    }
    
    public function addParentProperty( $property, $target_property, \App\AdminBundle\Compiler\Annotation\Annotation $annot ){
        $map        =  $this->admin_object->getPropertyDoctrineAssociationMapping( $property ) ;
        $target_class   = $map['targetEntity'] ;
        $this->throwPropertyError( $property, "can not set `%s->%s` value `%s`", $target_class, $target_property, json_encode( $annot) );
    }
    
    public function isRequestObject() {
        return false ;
    }
    
    public function isCustomize(){
        return false ;
    }
    
    public function isCreateTemplate(){
        return false ;
    }
    
    public function isCreateForm(){
        return false ;
    }
    
    public function isPropertyAuth(){
        return $this->isCreateForm() ;
    }
    
    public function isOwnerAuth(){
        return $this->isPropertyAuth() ;
    }
    
    public function isWorkflowAuth(){
        return false;
    }
    
    public function isCreateAction() {
        return false ;
    }
    
    public function isListAction() {
        return false ;
    }
    
    public function isViewAction() {
        return false ;
    }
    
    public function isBatchAction() {
        return false ;
    }
    
    
    final protected function set_template($value) {
        $this->final_template   = $value ;
    }
    
    /**
     * @return \App\AdminBundle\Compiler\Generator\PhpClass
     */
    public function getCompileClass() {
        if( null === $this->_compile_class ) {
            $this->_compile_class   = $this->admin_object->generator->getActionPhpGenerator( $this ) ; 
        }
        return  $this->_compile_class ;
    }
    
    
    /**
     * @return \App\AdminBundle\Compiler\Generator\PhpWriter
     */
    protected $_compile_form_writer = null ;

    /**
     * @return \App\AdminBundle\Compiler\Generator\PhpWriter
     */
    public function getCompileFormWriter() {
        if( null !== $this->_compile_form_writer ) {
            return $this->_compile_form_writer ;
        }
        $class  = $this->admin_object->getCompileClass() ;
        
        $fn =  'build' . ucfirst( $this->name  ) .'Form' ;
        
        $method = $class
                ->addMethod( $fn )
                ->setVisibility('public')
                ->addParameter(
                        \CG\Generator\PhpParameter::create('controller')
                        ->setType('\Symfony\Bundle\FrameworkBundle\Controller\Controller')
                    )
                ->addParameter(
                        \CG\Generator\PhpParameter::create('object')
                    )
                ->addParameter(
                        \CG\Generator\PhpParameter::create('builder')
                        ->setType('\Symfony\Component\Form\FormBuilder')
                    )
                ->addParameter(
                        \CG\Generator\PhpParameter::create('action')
                        ->setType('\\' . $this->_compile_class_name )
                    )
        ;
        
        $method->addLazyCode('$this->buildForm($controller, $builder, $action, $object);');
        
        $this->_compile_form_writer = $method->getWriter() ;
        return $this->_compile_form_writer ;
    }
    
    /**
     * @return \App\AdminBundle\Compiler\Generator\PhpClass
     */
    public function compile(){
        $class  = $this->getCompileClass() ;
        
        $class
                ->addProperty('name', $this->name ) 
                ->addProperty('admin_name', $this->admin_object->name ) 
                ->addProperty('admin_class', $this->admin_object->class_name ) 
                ->addProperty('action_name', $this->admin_object->name . '.' . $this->name ) 
                ->addProperty('icon',  $this->icon ) 
                ->addProperty('tr_domain',  $this->admin_object->tr_domain )
                ->addProperty('app_domain',  $this->admin_object->app_domain )
        ;
        
        if( $this->template || $this->final_template ) {
            $class->addProperty('template',  $this->final_template ?:  $this->template ) ;
        }
        
        $class
                ->addProperty('label', $this->label->getPath() ) 
                ->addProperty('label_domain', $this->label->getDomain() )
                ->addProperty('action_label', $this->action_label->getPath() ) 
                ->addProperty('action_label_domain', $this->action_label->getDomain() )
                ->addProperty('title_label', $this->title_label->getPath() ) 
                ->addProperty('title_label_domain', $this->title_label->getDomain() )
                ->addProperty('form_label', $this->form_label->getPath() ) 
                ->addProperty('form_label_domain', $this->form_label->getDomain() )
        ;
        
        $class->addProperty('is_workflow_action',  $this->isWorkflowAuth() , 'bool', null, 'public' ) ;
         
        if( $this->isCreateForm() ) {
            $this->compileForm() ;
        }
        
        return $class ;
    }
    
    public function compileForm(){
        
        $writer = $this->getCompileFormWriter() ;
        
        // add form, field name to action object
        $form   = $this->admin_object->form ;
        
        if( 1 === count($form->groups) ) {
            foreach($form->groups as $group) {
                foreach($group->properties as $property_name ) {
                    $element    = $form->children->properties[ $property_name ] ;
                    $element->compileActionForm( $this ) ;
                }
            }
        } else {
            foreach($form->groups as $group) {
                $group->compileForm( $this, '$builder', '$this', '$object',  $form->children, $writer );
            }
        }
        
        $form_elements  = array() ;
        foreach($form->children->properties as $element_name => $element ) {
            $form_elements[]    = $element_name ;
        }
        
        $this->_compile_class
                ->addProperty('form_elements', $form_elements ) ;
    }
}
