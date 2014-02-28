<?php

namespace App\AdminBundle\Compiler\Annotation ;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class FormType
{
    
    /** @var string */
    public $type ;
    
    /** @var string */
    public $map ;
    
    /** @var mixed */
    public $orm ;
    
    /** @var mixed */
    public $guess ;
    
    /** @var bool */
    public $default ;
    
    
    /**
     * Constructor
     *
     * @param array $data Key-value for properties to be defined in this class
     */
    public function __construct(array $data)
    {
        foreach ($data as $key => $value ) {
            if( 'value' === $key ) {
                $this->type = $value ;
            } else if( property_exists( $this, $key ) ) {
                $this->$key = $value;
            } else {
                $this->__set($key, $value ) ;
            }
        }
    }

    /**
     * Error handler for unknown property accessor in Annotation class.
     *
     * @param string $name Unknown property name
     *
     * @throws \BadMethodCallException
     */
    public function __get($name)
    {
        throw new \BadMethodCallException(
            sprintf("Unknown property '%s' on annotation '%s'.", $name, get_class($this))
        );
    }

    /**
     * Error handler for unknown property mutator in Annotation class.
     *
     * @param string $name Unkown property name
     * @param mixed $value Property value
     *
     * @throws \BadMethodCallException
     */
    public function __set($name, $value)
    {
        throw new \BadMethodCallException(
            sprintf("Unknown property '%s' on annotation '%s'.", $name, get_class($this))
        );
    }
    
}
