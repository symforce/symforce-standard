<?php

namespace App\AdminBundle\Compiler\Annotation ;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class Entity extends Annotation
{
    
    /** @var string */
    public $name ;
    
    /** @var string */
    public $label ;
    
    /** @var string */
    public $class ;
    
    /** @var string */
    public $icon ;
    
    /** @var mixed */
    public $menu ;
    
    /** @var mixed */
    public $dashboard ;
    
    /** @var mixed */
    public $tr_domain ;
    
    /** @var string */
    public $string ;
    
    /** @var array */
    public $groups ;
    
    /** @var string */
    public $template ;
    
    /** @var string */
    public $position ;


    public function __set($name, $value)
    {    
        if( 'value' === $name ) {
            $this->name  = $value ;
        } else {
            throw new \BadMethodCallException(
                sprintf("Unknown property '%s' on annotation '%s'.", $name, get_class($this))
            );
        }
    }

}
