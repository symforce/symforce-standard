<?php

namespace App\AdminBundle\Compiler\MetaType\Form\Mopa ;

/**
 * Description of Widget
 *
 * @author loong
 */
class Tooltip extends AbstractBase {
    
    public $title;
    
    public $icon ;
    
    public $placement ; // top|right|bottom|left
    
    public function __construct( $annot ) {
        if( is_array( $annot) || is_object($annot) ) {
            $this->setMyPropertie( $annot ) ;
        } else {
            $this->setTitle( $annot ) ;
        }
    }
    
            
    public function set_type( $value ) {
        $list   = array('top', 'right', 'bottom', 'left') ;
        if( !in_array($value, $list) ) {
            $this->throwError( sprintf("widget_type must be one of %s", join(',', $list ) ) ) ;
        }
        $this->placement = $value ;
    }
}