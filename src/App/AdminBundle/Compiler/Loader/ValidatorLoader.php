<?php

namespace App\AdminBundle\Compiler\Loader;

use Symfony\Component\Validator\Mapping\Loader\LoaderInterface ;
use Symfony\Component\Validator\Exception\MappingException;
use Symfony\Component\Validator\Mapping\ClassMetadata;


use Symfony\Component\Validator\Mapping\Loader\LoaderChain;
use Symfony\Component\Validator\Mapping\Loader\StaticMethodLoader;
use Symfony\Component\Validator\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Validator\Mapping\Loader\XmlFilesLoader;
use Symfony\Component\Validator\Mapping\Loader\YamlFilesLoader;

use Symfony\Bundle\FrameworkBundle\Validator\ConstraintValidatorFactory ;
use Symfony\Component\Validator\Mapping\ClassMetadataFactory ;

use Symfony\Component\Form\Extension\Validator\ValidatorTypeGuesser ;

/**
 * Description of ValidatorLoader
 *
 * @author loong
 */
class ValidatorLoader implements LoaderInterface {
    //put your code here
    
    /**
     * @var \App\AdminBundle\Compiler\Cache\AdminLoader
     */
    protected $loader ;

    public function setAdminLoader(AdminLoader $loader ) {
        $this->loader   = $loader ;
    } 
    
    public function loadClassMetadata(ClassMetadata $metadata) {
        
        $class_name = $metadata->getReflectionClass()->getName()  ;
        
        if( $this->loader->hasAdminClass($class_name) ) {
            $admin  = $this->loader->getAdminByClass($class_name) ;
            if( method_exists($admin, 'loadValidatorMetadata') ) {
                $admin->loadValidatorMetadata( $metadata ) ;
            }
            return true ;
        }
    }
}
