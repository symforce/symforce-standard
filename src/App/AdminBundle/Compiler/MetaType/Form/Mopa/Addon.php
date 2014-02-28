<?php

namespace App\AdminBundle\Compiler\MetaType\Form\Mopa ;

/**
 * Description of Widget
 *
 * @author loong
 */
class Addon extends AbstractBase {
    
    public $type ;
    
    public $icon ;
    
    public function set_type( $type ) {
        $list   = array('inline') ;
        if( !in_array($type, $list) ) {
            $this->throwError( sprintf("widget_type must be one of %s", join(',', $list ) ) ) ;
        }
        $this->type = $type ;
    }
    
    
}