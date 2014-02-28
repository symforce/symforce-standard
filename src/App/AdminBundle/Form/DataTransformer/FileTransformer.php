<?php

namespace App\AdminBundle\Form\DataTransformer ;

use Symfony\Component\Form\DataTransformerInterface ;
use Symfony\Component\Form\Exception\TransformationFailedException;

use App\AdminBundle\Entity\File ;
use App\AdminBundle\Entity\TmpFile ;
use Doctrine\ORM\Id\UuidGenerator ;

/**
 * Description of DatetimeTransformer
 *
 * @author loong
 */
class FileTransformer implements DataTransformerInterface {

    /**
     * Transforms an object (issue) to a string (number).
     *
     * @param  \File|null $value
     * @return string
     */
    public function transform($value) {
        if(is_object($value) ) {
            return  array(
                'url'   => $value->__toString() ,
                'name'  => $value->getName() ,
                'size'  => $value->getSize() ,
            ) ;
        }
        return array( 
                    'url'   => $value ,
                    'name'   => $value ,
                    'size'   => 0 ,
                ) ;
    }
    
    /**
     * Transforms a string (number) to an object (issue).
     *
     * @param  string $value
     * @return \File|null
     */
    public function reverseTransform($value){ 
        if( !$value ) {
            return null ;
        }
        if( is_object($value) ) {
            return $value ;
        } 
        
        return $value ;
    }
}