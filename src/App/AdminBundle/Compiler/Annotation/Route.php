<?php

namespace App\AdminBundle\Compiler\Annotation ;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class Route extends Annotation
{

    /** @var string */
    public $admin ;
    
    /** @var string */
    public $action ;
    
    /** @var string */
    public $alias ;
    
    /** @var bool */
    public $entity ;

    /** @var string */
    public $template ;
    
    /** @var string */
    public $name ;
    
    /** @var string */
    public $path ;
    
    /** @var string */
    public $host ;
    
    /** @var string */
    public $schemes ;
    
    /** @var string */
    public $methods ;
    
    /** @var array */
    public $defaults ;
    
    /** @var string */
    public $requirements ;
    
    public function __set($name, $value)
    {    
        if( 'value' === $name ) {
            $this->path  = $value ;
        } else {
            throw new \BadMethodCallException(
                sprintf("Unknown property '%s' on annotation '%s'.", $name, get_class($this))
            );
        }
    }
}
