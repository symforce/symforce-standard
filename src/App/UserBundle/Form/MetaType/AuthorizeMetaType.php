<?php

namespace App\UserBundle\Form\MetaType ;

use App\AdminBundle\Compiler\MetaType\Form\Element ;
use App\AdminBundle\Compiler\Annotation\FormType ;

/**
 * @FormType("appauthorize", orm="array")
 */
class AuthorizeMetaType extends Element {
    
    public $view = false ;
    
    public function getFormOptions(){
        $options    =  parent::getFormOptions() ;
        unset($options['required']) ;
        return $options ;
    }
}
