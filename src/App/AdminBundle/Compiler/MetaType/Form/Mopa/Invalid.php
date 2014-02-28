<?php

namespace App\AdminBundle\Compiler\MetaType\Form\Mopa ;

/**
 * Description of Widget
 *
 * @author loong
 */
class Invalid extends AbstractBase {
    
    public $message ;
    
    public $parameters;
    
    public function __construct( $annot ) {
        if( is_array( $annot) || is_object($annot) ) {
            $this->setMyPropertie( $annot ) ;
        } else {
            $this->setMessage( $annot ) ;
        }
    }
    
    
}
