<?php

namespace App\AdminBundle\Compiler\MetaType\Form ;

use App\AdminBundle\Compiler\Annotation\FormType ;

/**
 * @FormType(default="date")
 */
class  Date extends DateTime {
    public $format = 'Y-m-d' ;
    public function getFormOptions() {
       $options    = parent::getFormOptions() ; 
       $options['picker']   = 'date' ;
       return $options ;
    }
} 