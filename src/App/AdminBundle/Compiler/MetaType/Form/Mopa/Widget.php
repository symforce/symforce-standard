<?php

namespace App\AdminBundle\Compiler\MetaType\Form\Mopa ;

/**
 * Description of Widget
 *
 * @author loong
 */
class Widget extends AbstractBase {
    
    public $type ; // inline
    
    public $addon ;
    
    public $prefix ;
    
    public $suffix ;
    
    public $add_btn ;
    
    public $remove_btn ;
    
    public function set_type( $type ) {
        $list   = array('inline') ;
        if( !in_array($type, $list) ) {
            $this->throwError( sprintf("widget_type must be one of %s", join(',', $list ) ) ) ;
        }
        $this->type = $type ;
    }
    
    public function set_prefix( $value ) {
         $this->prefix = $value ;
    }
    
    public function set_suffix( $value ) {
         $this->suffix = $value ;
    } 
    public function set_addon( $annot ) {
        $this->addon    = new Addon($annot);
    }
     
    public function set_add_btn( $annot ) {
        $this->add_btn    = new WidgetBtn($annot);
    }
     
    public function set_remove_btn( $annot ) {
        $this->remove_btn    = new WidgetBtn($annot);
    }
    
}