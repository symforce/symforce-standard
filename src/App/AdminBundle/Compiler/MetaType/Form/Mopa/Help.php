<?php

namespace App\AdminBundle\Compiler\MetaType\Form\Mopa ;

/**
 * Description of Widget
 *
 * @author loong
 */
class Help extends AbstractBase {
    
    public $label ;
    
    public $inline ;
    
    public $block ;
    
    public $tooltip ;
    
    public $popover ;
    
    public function __construct( $annot ) {
        if( is_array( $annot) || is_object($annot) ) {
            $this->setMyPropertie( $annot ) ;
        } else {
            $this->setBlock( $annot ) ;
        }
    }
    
    
    public function set_tooltip( $annot ) {
        $this->tooltip   = new Tooltip($annot) ;
    }
    
    public function set_popover( $annot ) {
        $this->popover   = new Popover($annot) ;
    }
    
}
