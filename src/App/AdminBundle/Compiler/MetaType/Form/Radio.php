<?php

namespace App\AdminBundle\Compiler\MetaType\Form ;

class Radio extends Choice  {
    
    /** @var bool */
    public $multiple = false ;
    
    public function set_multiple( $value ) {
        $this->throwError("can not set multiple ");
    }
    

}