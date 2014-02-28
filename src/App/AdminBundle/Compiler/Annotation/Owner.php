<?php

namespace App\AdminBundle\Compiler\Annotation ;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
class Owner extends Annotation 
{
    
    /** @var string */
    public $id ;
    
    /** @var string */
    public $name ;
    
    /** @var string */
    public $title ;
    
    public function __set($name, $value)
    {    
        if( 'value' === $name ) {
            $this->id  = $value ;
        } else {
            throw new \BadMethodCallException(
                sprintf("Unknown property '%s' on annotation '%s'.", $name, get_class($this))
            );
        }
    }

}
