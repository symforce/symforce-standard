<?php

namespace App\AdminBundle\Compiler\MetaType\Action\Html ;

/**
 * Description of AbstractTag
 *
 * @author loong
 */
class Tag extends Base {
    
    public $tag ;
    public $css ;
    public $style ;
    public $id ;
    public $attrs = array() ;
    
    public function __construct( $annot ) {
        if( is_array( $annot ) || is_object( $annot ) ) {
            $this->setMyPropertie( $annot ) ;
        } else {
            $this->set_tag( $annot ) ;
        }
    }
    
    public function set_tag( $tag ) {
        $this->tag  = $tag ;
    }
    
    public function set_attrs( $attrs ) {
        $this->attrs    = (array) $attrs ;
    }
    
    public function compileTag( $innerHtml ){
        $attrs  = $this->attrs ;
        if( $this->css ) {
            $attrs['class'] = $this->css ;
        }
        if( $this->style ) {
            $attrs['style'] = $this->style ;
        }
        if( !$this->tag ) {
            $this->tag  = 'span' ;
        }
        $html  = '<' . $this->tag ;
        foreach( $attrs as $name => $attr ) if( $attr ) {
            $html  .= ' ' . $name . '="' . str_replace('"', '\\"', $attr ) . '"' ; 
        }
        $html  .= '>'. $innerHtml . '</' . $this->tag . '>' ;
        return $html ;
    }
}
