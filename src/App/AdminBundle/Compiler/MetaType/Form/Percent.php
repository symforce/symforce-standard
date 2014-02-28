<?php

namespace App\AdminBundle\Compiler\MetaType\Form ;

use App\AdminBundle\Compiler\Annotation\FormType ;
/**
 * @FormType(orm="integer,bigint,float,decimal", default=true )
 */
class Percent extends Integer {
    
    public $real_type ;
    public $precision ;

    public function set_real_type( $type ) {
        $this->real_type = $type ;
    }

    public function set_precision( $precision ) {
        $this->precision = (int) $precision ;
    }

    public function set_max( $value ){
        $this->max  = doubleval($value)  ;
        if( null !== $this->min ) {
            if( $this->min >= $this->max ) {
                $this->throwError( " min:%s is bigger than max:%s", $this->min, $this->max );
            }
        }
    }
    
    public function set_min( $value ){
        $this->min = doubleval($value)  ;
        if( null !== $this->max ) {
            if( $this->min >= $this->max ) {
                $this->throwError( " min:%s is bigger than max:%s", $this->min, $this->max ) ;
            }
        }
    }
    
    
    public function getFormOptions() {
        $float_type = array( 'float' , 'decimal' ) ;
        if( $this->real_type ) {
            if( in_array( $this->compile_orm_type, $float_type)  !== in_array($this->real_type, $float_type) ) {
                $this->throwError("real_type is not match `doctrine:%s` ", $this->real_type , $this->compile_orm_type  ) ;
            }
        } else {
            $this->real_type    = $this->compile_orm_type ;
        }
        $options    = parent::getFormOptions() ;
        return $options ;
    }
    
}