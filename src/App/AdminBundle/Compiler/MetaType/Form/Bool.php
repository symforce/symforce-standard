<?php

namespace App\AdminBundle\Compiler\MetaType\Form ;

use App\AdminBundle\Compiler\Annotation\FormType ;

/**
 * @FormType("choice", orm="boolean", default=true)
 */
class Bool extends Choice {
    
    /** @var bool */
    public $expanded = true ;
    
    /**
     * @var string
     */
    protected $yes = null ;
    
    /**
     * @var string
     */
    protected $no = null ;
    
    public function set_yes( $value ) {
        if( is_object($value) || is_array($value) ) {
            $this->yes  = new Option( $value , null, 'yes') ;
        } else {
            $this->yes  = new Option( 1, $value , 'yes' ) ;
        }
    }
    
    public function set_no( $value ) {
        if( is_object($value) || is_array($value) ) {
            $this->no  = new Option( $value , null, 'no') ;
        } else {
            $this->no  = new Option( 0, $value, 'no' ) ;
        }
    }
    
    public function set_expanded( $value ) {
        $this->throwError( "can not set expanded for bool") ;
    }
    
    public function set_choices( $value ) {
        $this->throwError("can not set choices for bool") ;
    }
    
    public function set_multiple( $value ) {
        $this->throwError("can not set multiple for bool") ;
    }
    
    public function set_empty_value( $value ) {
        $this->throwError("can not set empty_value for bool") ;
    }
    
    public function getChoices() {
        if( !$this->yes ) {
            $this->yes   = new Option( 1, 'Yes' , 'yes' )  ;
            $this->yes->setLabel( $this->tr_node, 'app.form.choices.yesno' , $this->admin_object->app_domain ); 
        } else {
            $this->yes->setLabel( $this->tr_node, 'choices' );
        }
        if( ! $this->no ) {
            $this->no   = new Option( 0, 'No' , 'no')  ;
            $this->no->setLabel( $this->tr_node, 'app.form.choices.yesno' , $this->admin_object->app_domain );
        } else {
            $this->no->setLabel( $this->tr_node, 'choices' );
        }
        return array(
            $this->yes  ,
            $this->no ,
        );
    }
    
    public function getFormOptions() {
        $options    = parent::getFormOptions() ;
        $options['widget_type'] = 'inline' ;
        return $options ;
    }
    
}