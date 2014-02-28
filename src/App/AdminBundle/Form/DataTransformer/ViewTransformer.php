<?php

namespace App\AdminBundle\Form\DataTransformer ;

use Symfony\Component\Form\DataTransformerInterface ;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Description of DatetimeTransformer
 *
 * @author loong
 */
class ViewTransformer implements DataTransformerInterface {
    
    /**
     * @var \object
     */
    private $reverse_data ;
    
    /**
     * Transforms an object (issue) to a string (number).
     *
     * @param  object|null $value
     * @return string
     */
    public function transform($value) {
        if( !$value ) {
            return 0 ;
        }
        if( !is_object($value) ) {
            return $value ;
        }
        if( !is_array($value) ) {
             return '' ;
        }
        return '' ;
    }
    
    /**
     * Transforms a string (number) to an object (issue).
     *
     * @param  string $value
     * @return object|null
     */
    public function reverseTransform($value) {
        return $this->reverse_data  ;
    }
    
    public function setReverseData( $data ) {
        $this->reverse_data  = $data ;
    }
}
