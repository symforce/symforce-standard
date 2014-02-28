<?php

namespace App\AdminBundle\Compiler\MetaType\Form ;
use App\AdminBundle\Compiler\Annotation\FormType ;

/**
 * @FormType(orm="text", default=true)
 */
class Textarea extends Text {
    
    public $required ;
    
    public $max_length  = 0x7fff ;
    public $hetigh = 12 ;
    
    public function set_height( $value ) {
        // todo check it
        $this->hetigh = $value ;
    }
    
    public function getFormOptions() { 
        $options    = parent::getFormOptions() ;
        
        $options['attr']['rows']  = $this->hetigh ;
        
        return $options ;
    }
}