<?php

namespace App\AdminBundle\Compiler\MetaType\Form ;

use App\AdminBundle\Compiler\Annotation\FormType ;

/**
 * @FormType("appcolor", orm="string")
 */
class Color extends Element {
    
    public function getFormOptions(){
        $_options    = parent::getFormOptions() ; 
        
        $options    = array(
            'attr'  => array(
                'type'  => 'text' ,
                'class'   => 'colorpicker form-control not-removable' ,
            )
        ) ;
        
        return array_merge($_options, $options)  ;
    }
}