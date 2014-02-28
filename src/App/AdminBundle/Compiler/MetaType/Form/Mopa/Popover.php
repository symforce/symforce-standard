<?php

namespace App\AdminBundle\Compiler\MetaType\Form\Mopa ;

/**
 * Description of Widget
 *
 * @author loong
 */
class Popover extends AbstractBase {
    
    public $title;
    
    public $content ;
    
        
    public function __construct( $annot ) {
        if( is_array( $annot) || is_object($annot) ) {
            $this->setMyPropertie( $annot ) ;
        } else {
            $this->setContent( $annot ) ;
        }
    }
    
}
