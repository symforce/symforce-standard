<?php

namespace App\AdminBundle\Compiler\MetaType\Form ;

use App\AdminBundle\Compiler\Annotation\FormType ;

/**
 * @FormType("appowner", map="App\UserBundle\Entity\User")
 */
class Owner extends Element {
    
    public function getFormOptions(){
        $options    = parent::getFormOptions() ;
        $options['choices'] = $this->compilePhpCode( '$this->getOwnerFormChoices($object)' ) ;
        $options['admin_class']  = $this->admin_object->class_name ;
        
        return $options ;
    }
}
