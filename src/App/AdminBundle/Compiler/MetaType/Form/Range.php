<?php

namespace App\AdminBundle\Compiler\MetaType\Form ;

use App\AdminBundle\Compiler\Annotation\FormType ;
/**
 * @FormType("apprange", orm="integer,bigint,float,decimal")
 */
class Range extends Integer {
   
    public $min = 1 ;
    public $max = 9999 ;
    public $unit ;
    public $_unit ;
    public $icon ;
    public $_icon ;
    
    public $divisor = 1 ;
    public $precision = 0 ;
    public $grouping = false ;
    
    public $choices ;
    public $choice_code ;
    
    protected function set_choices( $value ){
        if( !is_array($value) ) {
            $this->throwError("choices must be array");
        }
        $keys   = array_keys( $value ) ;
        foreach($keys as $i => $_i ) {
            if( $i !== $_i ) {
                $this->throwError("choices must be array, you use hash");
            }
        }
        $this->choices = $value ;
    }
    
    
    public function lazyInitialize() {
        parent::lazyInitialize();
       if( null !== $this->unit ) {
            if( null === $this->_unit  ) {
                $this->_unit = $this->tr_node->createValue( 'unit' , $this->unit ) ;
            }
            $options['unit']    = array( $this->_unit->getPath(), $this->_unit->getDomain() ) ;
        }
    }
    
    public function getFormOptions() {
        $options    = parent::getFormOptions() ;
        
        $options['max'] = $options['attr']['max'] ;
        $options['min'] = $options['attr']['min'] ;

        if( $this->_unit ) {
            $options['unit']    = array( $this->_unit->getPath(), $this->_unit->getDomain() ) ;
        }
        
        if( null !== $this->icon ) {
            if( null === $this->_icon  ) {
                $this->_icon = $this->tr_node->createValue( 'icon' , $this->icon ) ;
            }
            $options['unit_icon']    = array( $this->_icon->getPath(), $this->_icon->getDomain() ) ;
        }
        
        if( null !== $this->choices ) {
            foreach($this->choices as $value){
                if( $value < $options['min'] ) {
                    $this->throwError("choices value(%s) small than %s", $value , $options['min'] );
                } else if( $value > $options['max'] ) {
                    $this->throwError("choices value(%s) bigger than %s", $value , $options['max'] );
                }
            }
            $options['choices']    = $this->choices ;
        } 
        
        if( null !== $this->choice_code ) {
            if( $this->choices ) {
                $this->throwError(' can not use choices_code with choices') ;
            } 
            if( false == strpos($this->choice_code, '$') ) {
                $options['choices']    = $this->compilePhpCode(sprintf('$this->%s($object)', $this->choice_code) ) ;
            } else {
                $options['choices']    = $this->compilePhpCode($this->choice_code ) ;
            }
        }
        if( 'integer' ===  $this->compile_orm_type ) {
            $options['attr']['maxlength']    = strlen($options['max']) + 1 ;
        } else {
            $options['attr']['maxlength']    = strlen($options['max']) + 4 ;
        }
        
        if( null !== $this->precision ) {
             $options['float_option']    = array(
                 'precision'    => $this->precision ,
                 'divisor'    => $this->divisor ,
                 'grouping'    => $this->grouping ,
             ) ;
        }
        
        return $options ;
    }
    
}
