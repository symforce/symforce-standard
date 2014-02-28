<?php

namespace App\AdminBundle\Compiler\Annotation ;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class Action extends Annotation
{
    
    /** @var string */
    public $name ;
    
    /** @var string */
    public $label ;
    
    /** @var string */
    public $action_label ;
    
    /** @var string */
    public $title_label ;
    
    /** @var string */
    public $icon ;
    
    /** @var integer */
    public $dashboard ;
    
    /** @var string */
    public $template ;
    
    /** @var integer */
    public $table ;
    
    /** @var integer */
    public $toolbar ;
    
    public function __set($name, $value)
    {
        if( $name == 'value' ) {
            $this->name   = $value ;
        } else {
            throw new \BadMethodCallException(
                sprintf("Unknown property '%s' on annotation '%s'.", $name, get_class($this))
            );
        }
    }

    public function getArrayKeyProperty() {
        return 'name' ;
    }
}
