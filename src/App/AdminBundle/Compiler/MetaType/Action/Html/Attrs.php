<?php

namespace App\AdminBundle\Compiler\MetaType\Action\Html ;

/**
 * Description of AbstractTag
 *
 * @author loong
 */
class Attrs extends Base {
    
    public function __construct( $annot ) {
        if( is_array( $annot ) || is_object( $annot ) ) {
            $this->setMyPropertie( $annot ) ;
        } else {
            $this->set_code( $annot ) ;
        }
    }
}
