<?php

namespace App\AdminBundle\Form\DataTransformer ;

use Symfony\Component\Form\DataTransformerInterface ;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Description of DatetimeTransformer
 *
 * @author loong
 */
class DateTimeTransformer implements DataTransformerInterface {
    
    private $format ;
    private $type ;
    
    public function __construct( $format, $type ) {
        $this->format   = $format ;
        $this->type   = $type ;
    }
    
    /**
     * Transforms an object (issue) to a string (number).
     *
     * @param  \DateTime|null $value
     * @return string
     */
    public function transform($value){
        if( !$value ) {
            return null ;
        }
        if( is_string($value) ) {
            return $value ;
        }
        return $value->format( $this->format )  ;
    }
    
    /**
     * Transforms a string (number) to an object (issue).
     *
     * @param  string $value
     * @return \DateTime|null
     */
    public function reverseTransform($value){ 
        if( empty($value) ) {
            return null ;
        }
        if( $value instanceof \DateTime){
            return $value ;
        }
        $date   = date_create_from_format($this->format, $value) ;
        if( !$date ) {
            return null ;
        }
        return $date ;
    }
}

