<?php

namespace App\AdminBundle\Compiler\MetaType ;

abstract class PropertyAbstract extends Type {

    /**
     * @var \App\AdminBundle\Compiler\MetaType\Admin\Entity
     */
    public $admin_object ;
    
    /**
     * @var string 
     */
    public $class_property ;
    
    /**
     * @var PropertyContainer
     */
    public $property_container ;
    
    /**
     * @return \App\AdminBundle\Compiler\Generator\TransGeneratorValue
     */
    public $label ;
    
    /**
     * @var \App\AdminBundle\Compiler\Generator\TransGeneratorNode 
     */
    public $tr_node ;
    
    public function __construct(PropertyContainer $property_container, \App\AdminBundle\Compiler\MetaType\Admin\Entity $entity, $property, \App\AdminBundle\Compiler\Annotation\Annotation $annot = null ) {

        $this->property_container    = $property_container ;
        $this->class_property   = $property ;
        $this->admin_object     = $entity ;
        
        if( $annot ) {
            $this->setMyPropertie( $annot ) ;
        }
        
        $property_container->addProperty( $this ) ;
    }
    
    final protected function set_property($value) {
        
    }
    
    final protected function set_type($value) {
        
    }

    final  protected function set_class_property(){
        $this->throwError("can not set class_property");
    }
    
    
    /**
     * @return \App\AdminBundle\Compiler\MetaType\DoctrineType
     */
    public function isDoctrineMappped () {
        return $this->admin_object->isMappedProperty( $this->class_property  ) ;
    }
    
    public function isFieldProperty() {
        return $this->admin_object->isFieldProperty( $this->class_property  ) ;
    }
    
    public function isUniqueField(){
        if( ! $this->isFieldProperty() ) {
            return false ;
        }
        return $this->admin_object->orm_metadata->isUniqueField( $this->class_property  )  ;
    }
    
    public function isNullable(){
        if( ! $this->isFieldProperty() ) {
            return false ;
        }
        return $this->admin_object->orm_metadata->isNullable( $this->class_property )  ;
    }
    
    /**
     * 
     * @return string
     */
    public function getPropertyDoctrineType() {
        return $this->admin_object->getPropertyDoctrineType( $this->class_property  ) ;
    }
    
    /**
     * @return \Doctrine\ORM\Mapping\ClassMetadata
     */
    public function getEntityMetadata() {
        return $this->admin_object->orm_metadata ;
    }
    
    /**
     * 
     * @return array
     */
    public function getPropertyDoctrineAssociationMapping() {
        return $this->admin_object->getPropertyDoctrineAssociationMapping( $this->class_property  ) ;
    }
    
    public function throwError() {
        $args   = func_get_args() ;
        $argv   = count($args) ;
        if( !$argv ) {
            $msg    = '' ;
        } else if( $argv == 1 ) {
            $msg    = $args[0] ;
        } else {
            $msg    = call_user_func_array('sprintf', $args ) ;
        }
        
        $_msg   = $this->_throw_append_message ;
        $this->_throw_append_message = null ;
        
        if( $_msg ) {
            throw new Exception( sprintf("%s for @%s%s, from %s->%s",  $msg, $this->getMeteTypeName(), $_msg, $this->admin_object->class_name, $this->class_property ) ) ;
        } else {
            throw new Exception( sprintf("%s for @%s, from %s->%s",  $msg, $this->getMeteTypeName(), $this->admin_object->class_name, $this->class_property ) ) ;
        }
    }
}