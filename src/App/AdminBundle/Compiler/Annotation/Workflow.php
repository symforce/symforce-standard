<?php

namespace App\AdminBundle\Compiler\Annotation ;


/**
 * @Annotation
 * @Target({"CLASS"})
 */
class Workflow extends Annotation {
    
    /** @var array */
    public $status ;
    
    /** @var string */
    public $property ;
    
    /** @var string */
    public $properties ;
    
    /** @var array */
    public $permertions ;

    /**
     * @param array $data Key-value for properties to be defined in this class
     */
    public function __construct(array $data)
    {
        foreach ($data as $key => $value) {
            if( $key === 'value' ) {
                $this->property = $value ;
            } else if( property_exists( $this, $key ) ) {
                $this->$key = $value;
            } else {
                $this->__set($key, $value ) ;
            }
        }
    }
}
