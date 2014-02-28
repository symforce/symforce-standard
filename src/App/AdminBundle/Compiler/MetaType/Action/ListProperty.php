<?php

namespace App\AdminBundle\Compiler\MetaType\Action ;

use App\AdminBundle\Compiler\MetaType\Form ;

class ListProperty extends ViewProperty {
    
    protected function checkAnnotChange() {
        if( !empty($this->_annot_properties) ) {
            $ignor_properties   = array( 'property', 'position', 'order', 'width', 'th', 'td' );
            foreach($this->_annot_properties as $property_name => $changed ) {
                if( !in_array($property_name, $ignor_properties) ) {
                    $this->_annot  = true ;
                    break ;
                }
            }
            
            $ignor_label_properties   = array( 'label' );
            foreach($ignor_label_properties as $property_name ) {
                if( isset( $this->_annot_properties[$property_name]) ) {
                    $this->_annot_label   = true ;
                    break ;
                }
            }
        }
    }
    
    /** @var bool */
    public $order ;
    
    public $width ;
    
    public function set_order( $value ) {
        $this->order = $value ;
    }
    
    public function set_width( $value ) {
        $this->width    = $value ;
    }
    
    /**
     * @var Html\Th 
     */
    public $th ;
    public function set_th( $annot ) {
        $this->th    = new Html\Th($annot) ;
    }
    
    /**
     * @var Html\Td 
     */
    public $td ;
    public function set_td( $annot ) {
        $this->td    = new Html\Td($annot) ;
    }
    
    public function compileTh() {
        $th = $this->th ?: new Html\Th( null ) ;
        $code = $this->getCompileLabelCode() ;
        if( $this->order ) {
             if( $this->admin_object->orm_metadata->hasField( $this->class_property ) ) {
                 $code  = sprintf("{{ knp_pagination_sortable(pagination, %s, 'a.%s') }}", $code , $this->class_property );
             } else if( $this->admin_object->orm_metadata->hasAssociation( $this->class_property )  ) {
                 $code  = sprintf("{{ knp_pagination_sortable(pagination, %s, 'a.%s_id') }}", $code , $this->class_property );
             } else {
                 $this->throwError("can not use order with pure property(%s, not orm manager)", $this->class_property );
             }
             
        }  else {
            $code   = '{{ ' . $code . ' }}' ;
        }
        return  $th->compileTag(  $code )  ;
    }
    
    public function compileTd() {
        $td = $this->td ?: new Html\Td( null ) ;
        return $td->compileTag( $this->getCompileValueCode() ) ;
    }
    
}
