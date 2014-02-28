<?php

namespace App\AdminBundle\Compiler\Annotation ;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
class Page extends Annotation 
{
    /** @var string */
    public $label ;
    
    /** @var string */
    public $title ;
    
    /** @var string */
    public $description ;
    
    /** @var string */
    public $keywords ;

    /** @var string */
    public $parent ;
    
    /** @var string */
    public $controller ;
    
    /** @var string */
    public $path ;

    
    public function __set($name, $value)
    {    
        if( 'value' === $name ) {
            $this->path = $value ;
        } else {
            throw new \BadMethodCallException(
                sprintf("Unknown property '%s' on annotation '%s'.", $name, get_class($this))
            );
        }
    }

}
