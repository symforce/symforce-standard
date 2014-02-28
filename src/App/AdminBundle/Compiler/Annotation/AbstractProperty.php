<?php

namespace App\AdminBundle\Compiler\Annotation ;


abstract class AbstractProperty extends Annotation
{
    
    /** @var string */
    public $label ;
    
    /** @var string */
    public $property ;
    
    public function __set($name, $value)
    {
        if( 'value' === $name ) {
            $this->property   = $value ;
        } else {
            throw new \BadMethodCallException(
                sprintf("Unknown property '%s' on annotation '%s'.", $name, get_class($this))
            );
        }
    }
}
