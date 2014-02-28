<?php

namespace App\AdminBundle\Compiler\MetaType\Action\Html ;

/**
 * Description of AbstractTag
 *
 * @author loong
 */
class Href extends Tag {
    
    public $url ;
    
    public $tag = 'a' ;
    
    public function __construct( $annot ) {
        if( is_array( $annot ) || is_object( $annot ) ) {
            $this->setMyPropertie( $annot ) ;
        } else {
            $this->url = $annot ;
        }
    }
    
    public function set_name( $name ){
        $this->throwPropertyError($name, "can not has name") ;
    }
    
    
    public function compileTag( $innerHtml ) { 
        $this->attrs['href']    = $this->url ;
        return parent::compileTag( $innerHtml ) ;
    }
}
