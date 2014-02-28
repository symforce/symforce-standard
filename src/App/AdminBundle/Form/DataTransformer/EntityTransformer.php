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
class EntityTransformer implements DataTransformerInterface {
    
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container A ContainerInterface instance
     *
     */
    public function setContainer(\Symfony\Component\DependencyInjection\ContainerInterface $container = null)
    {
        $this->container = $container;
    }
     
    /**
     * @var \App\AdminBundle\Compiler\Loader\AdminLoader
     */
    private $admin_loader ;
    
    /**
     * @var string
     */
    private $entity_class ;

    public function __construct($entity_class ) {
        $this->entity_class = $entity_class ;
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
        $this->admin_loader = $this->container->get('app.admin.loader') ;
        $admin  = $this->admin_loader->getAdminByClass($this->entity_class) ;
        return $admin->getId($value) ;
    }
    
    /**
     * Transforms a string (number) to an object (issue).
     *
     * @param  string $value
     * @return object|null
     */
    public function reverseTransform($value) {
        if( empty($value) ) {
            return null ;
        }
        $this->admin_loader = $this->container->get('app.admin.loader') ;
        $admin  = $this->admin_loader->getAdminByClass($this->entity_class) ;
        return $admin->getRepository()->find( $value ) ;
    }
    
}
