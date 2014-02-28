<?php

namespace App\AdminBundle\Compiler\MetaType\Action ;

class ActionProperty extends \App\AdminBundle\Compiler\MetaType\PropertyAbstract {
    
    /** @var integer */
    public $position ;
    
    public $icon ;
    
    // ========== config properties
    
    /** @var bool */
    public $template ;
    
    /** @var string */
    public $code ;
    
    // ========== system properties
    
    /**
     * @var \App\AdminBundle\Compiler\MetaType\Form\Element
     */
    public $form_element ;
    
    private $lazy_initialized ;

    public function lazyInitialize() {
        if( $this->lazy_initialized  ) {
            throw new \Exception('big error') ;
        }
        $this->lazy_initialized = true ;
        
        if( !$this->label ) {
            $this->label     = $this->admin_object->getPropertyLabel( $this->class_property ) ;
        } else {
            $this->label     = $this->property_container->tr_node->createValue( $this->class_property  . '.label', $this->label ) ;
        }
        
        // @todo fix this
        if( $this->admin_object->form->children->hasProperty( $this->class_property  ) ) {
            $this->form_element    = $this->admin_object->form->children->properties[ $this->class_property ] ;
        }
        
        if( $this->template && $this->code ) {
            $this->throwError( "can not set code and template same time" );
        }
    }
    
    public function set_code ( $value ) {
        $this->code  = $value ;
    }

}
