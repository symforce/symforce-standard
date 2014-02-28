<?php

namespace App\AdminBundle\Compiler\MetaType\Form\Mopa ;

use App\AdminBundle\Compiler\MetaType\BaseType ;

/**
 * Description of AbstractTag
 *
 * @author loong
 */
abstract class AbstractBase extends BaseType {
    
    public function __construct( $annot ) {
        if( is_array( $annot) || is_object($annot) ) {
            $this->setMyPropertie( $annot ) ;
        } else {
            $this->throwError("must be array") ;
        }
    }
   
}
