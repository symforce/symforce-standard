<?php

namespace App\AdminBundle\Compiler\MetaType\Form ;

use App\AdminBundle\Compiler\Annotation\FormType ;

/**
 * @FormType(orm="integer")
 */
class Entity extends Choice {
    
    /**
     * @var string
     */
    public $group_by ;

    public function getFormOptions(){
        $_options    =  parent::getFormOptions() ; 
        $map    = $this->admin_object->orm_metadata->getAssociationMapping( $this->class_property ) ;
        
        /*
        $admin  = $this->admin_object->generator->getAdminByClass( $map['targetEntity'] ) ;
        */
        
        if( null !== $this->choice_code ) {
            $_options['appform_type'] = 'appentity' ;
            $_options['entity_class']   = $map['targetEntity'] ;
        } else if( null !== $this->choice_code ){
            \Dev::dump($this->choice_code); exit;
        } else {
            $_options['class']  = $map['targetEntity'] ;
            if (  null !== $this->group_by ) {
                 $_options['group_by']  =  $this->group_by ;
            }   
        }
        
        return $_options ; 
    }
}
