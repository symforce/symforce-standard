<?php

namespace App\AdminBundle\Form\DataTransformer ;

use Symfony\Component\Form\DataTransformerInterface ;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Description of DatetimeTransformer
 *
 * @author loong
 */
class OwnerTransformer implements DataTransformerInterface {

    
    /**
     * @var \App\AdminBundle\Compiler\Loader\AdminLoader
     */
    private $admin_loader ;
    private $admin_class ;
    private $reverse_data ;

    public function __construct(\App\AdminBundle\Compiler\Loader\AdminLoader $admin_loader, $admin_class ) {
        $this->admin_loader = $admin_loader ;
        $this->admin_class = $admin_class ;
    }
    
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
        $admin  = $this->admin_loader->getAdminByClass($this->admin_class)->getOwnerAdmin() ;
        return $admin->getId($value) ;
    }
    
    /**
     * Transforms a string (number) to an object (issue).
     *
     * @param  string $value
     * @return \File|null
     */
    public function reverseTransform($value) {
        
        if( $this->reverse_data ) {
            // check if not allow edit, return reverse_data 
            return $this->reverse_data ;
        }
        
        $admin  = $this->admin_loader->getAdminByClass($this->admin_class)->getOwnerAdmin() ;
        return $admin->getObjectById( $value ) ;
    }
    
    public function setReverseData($data) {
        $admin  = $this->admin_loader->getAdminByClass($this->admin_class) ;
        $this->reverse_data = $admin->getObjectOwner( $data ) ;
    }
}
