<?php

namespace App\AdminBundle\Compiler\MetaType\Form ;

use App\AdminBundle\Compiler\Annotation\FormType ;

/**
 * @FormType()
 */
class Money  extends Percent {

    /** @var string */
    public $currency ;

    /** @var integer */
    public $divisor ;
    
    public $precision = 2 ;

    function set_currency( $currency ){
        // @todo add check
        $this->currency    = $currency ;
    }
    
    function set_divisor( $divisor ){
        $this->divisor    = $divisor ;
    }
    
    public function getFormOptions() {
        $options    = parent::getFormOptions() ;
        
        if( null !== $this->currency ) {
            $options['currency'] = $this->currency ;
        }
        if( null !== $this->divisor ) {
            $options['divisor'] = $this->divisor ;
        }
        
        $options['precision'] = $this->precision ;
        
        return $options ;
    }
    
}