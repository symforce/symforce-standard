<?php

namespace App\AdminBundle\Compiler\MetaType\Action\Html ;

/**
 * Description of AbstractTag
 *
 * @author loong
 */
class Tr extends Tag {
    
    public $tag = 'tr' ;
    
    public function __construct( $annot ) {
        if( is_array( $annot ) || is_object( $annot ) ) {
            $this->setMyPropertie( $annot ) ;
        } else if( $annot) {
            $this->set_code( $annot ) ; 
        }
    }

    public function set_name( $name ){
        $this->throwPropertyError($name, "can not has name") ;
    }
}
