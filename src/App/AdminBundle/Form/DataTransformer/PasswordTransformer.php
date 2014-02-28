<?php

namespace App\AdminBundle\Form\DataTransformer ;

use Symfony\Component\Form\DataTransformerInterface ;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Description of DatetimeTransformer
 *
 * @author loong
 */
class PasswordTransformer implements DataTransformerInterface {

    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container ;

    /**
     * @var \App\AdminBundle\Compiler\Loader\AdminLoader
     */
    private $admin_loader ;
    
    private $password_property ;
    private $salt_property ;
    private $plain_property ;
    private $entity_class ;
    
    private $password_value ;
    private $salt_value ;
    
    private $object_hash ;
    
    public function __construct(\Symfony\Component\DependencyInjection\ContainerInterface $container, $password_property, $salt_property ) {
        $this->container = $container ;
        $this->password_property = $password_property ;
        $this->salt_property = $salt_property ;
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
        return '' ;
    }
    
    /**
     * Transforms a string (number) to an object (issue).
     *
     * @param  string $value
     * @return \File|null
     */
    public function reverseTransform($value) {
        if( !$this->admin_loader ) {
            $this->admin_loader = $this->container->get('app.admin.loader') ;
        }
        if( $value && !empty($value) ) {
            $admin  = $this->admin_loader->getAdminByClass($this->entity_class) ;
            $admin->addEvent('update', function( $object, $admin ) use( $value ) {
                if( spl_object_hash( $object ) !== $this->object_hash  ) {
                    throw new \Exception("error") ;
                }
                $encoder = $this->container->get('security.encoder_factory')->getEncoder( $object ) ;
                $password  = $encoder->encodePassword( $value , $this->salt_value ) ;
                $admin->getReflectionProperty( $this->password_property )->setValue( $object, $password ) ;
            });
        }
        return $value ;
    }
    
    public function setReverseData( $object, $property ) {
        if( !$this->admin_loader ) {
            $this->admin_loader = $this->container->get('app.admin.loader') ;
        }
        $this->plain_property   = $property ;
        $this->entity_class     = get_class($object) ;
        
        $admin  = $this->admin_loader->getAdminByClass( $this->entity_class ) ;
        
        $this->salt_value  = $admin->getReflectionProperty( $this->salt_property )->getValue( $object ) ;
        $this->password_value  = $admin->getReflectionProperty( $this->password_property )->getValue( $object ) ;
        
        $this->object_hash  = spl_object_hash( $object ) ;
    }
}
