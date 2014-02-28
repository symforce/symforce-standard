<?php

namespace App\AdminBundle\Doctrine\DBAL\Listener;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Events  as DBALEvents;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\EventArgs;

use Doctrine\ORM\Events ;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\LifecycleEventArgs;

use Doctrine\ORM\Id\UuidGenerator ;

class AdminListener implements EventSubscriber {
    
    const HTML_PATTERN  = '/(?<=\W)\/upload\/html\/([0-9a-f]{8}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{12})\.(\w+)(?=\W)/' ;
    
    const preUpdate = 0 ;
    const postUpdate = 1 ;
    
    const preRemove = 2 ;
    const postRemove = 3 ;
    
    const preFlush = 4 ;
    const onFlush = 5 ;
    const postFlush = 6 ;
    
    const postLoad = 7 ;
    const onClear = 8 ;
    
    /**
     * @var UuidGenerator 
     */
    private $uuid_generator ;
    
    private $post_flush_persist_counter ;
    
    public function __construct() {
        $this->uuid_generator   = new UuidGenerator() ;
    }


    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     *
     * @api
     */
    protected $container;

    /**
     * Sets the Container associated with this Controller.
     *
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container A ContainerInterface instance
     *
     * @api
     */
    public function setContainer(\Symfony\Component\DependencyInjection\ContainerInterface $container = null)
    {
        $this->container = $container;
    }
    
    /**
     * @var ContainerInterface
     *
     * @api
     */
    protected $configs = null ;
    
    
    public function getSubscribedEvents(){
        return array(
            Events::preRemove ,
            Events::postRemove ,
            
            Events::prePersist ,
            Events::postPersist ,
            Events::preUpdate ,
            Events::postUpdate ,
            // Events::onFlush ,
            Events::preFlush ,
            Events::postFlush ,
        );
    }
    
    public function preRemove(LifecycleEventArgs $args) {
         if( null === $this->configs ) {
             // can not load configure before loadClassMetadata
             $this->configs  = $this->container->get('app.admin.loader')->getDoctrineConfig() ;
         }
         $this->onEvent($args->getEntityManager() , $args->getEntity(), self::preRemove ) ;
    }
    
    public function postRemove(LifecycleEventArgs $args) {
         if( null === $this->configs ) {
             // can not load configure before loadClassMetadata
             $this->configs  = $this->container->get('app.admin.loader')->getDoctrineConfig() ;
         }
         $this->onEvent($args->getEntityManager() , $args->getEntity(), self::postRemove ) ;
    }
    
    public function prePersist(LifecycleEventArgs $args) {
         if( null === $this->configs ) {
             // can not load configure before loadClassMetadata
             $this->configs  = $this->container->get('app.admin.loader')->getDoctrineConfig() ;
         }
         $this->onEvent($args->getEntityManager() , $args->getEntity(), self::preUpdate ) ;
    }
    
    public function postPersist(LifecycleEventArgs $args) {
         if( null === $this->configs ) {
             // can not load configure before loadClassMetadata
             $this->configs  = $this->container->get('app.admin.loader')->getDoctrineConfig() ;
         }
         $this->onEvent($args->getEntityManager() , $args->getEntity(), self::postUpdate ) ;
    }
    
    public function preUpdate(\Doctrine\ORM\Event\PreUpdateEventArgs $args) {
         if( null === $this->configs ) {
             // can not load configure before loadClassMetadata
             $this->configs  = $this->container->get('app.admin.loader')->getDoctrineConfig() ;
         }
         $this->onEvent($args->getEntityManager() , $args->getEntity(), self::preUpdate ) ;
    }
    
    public function postUpdate(LifecycleEventArgs $args) {
         if( null === $this->configs ) {
             // can not load configure before loadClassMetadata
             $this->configs  = $this->container->get('app.admin.loader')->getDoctrineConfig() ;
         }
         $this->onEvent($args->getEntityManager() , $args->getEntity(), self::postUpdate ) ;
    }
    
    public function onFlush(EventArgs $args) {
         if( null === $this->configs ) {
             // can not load configure before loadClassMetadata
            $this->configs  = $this->container->get('app.admin.loader')->getDoctrineConfig() ;
         }
         $om    = $args->getEntityManager();
         $uow = $om->getUnitOfWork();
         foreach($uow->getScheduledEntityInsertions() as $object){
             $this->onEvent($om, $object, self::onFlush );
         }
         foreach($uow->getScheduledEntityUpdates() as $object){
             $this->onEvent($om, $object, self::onFlush );
         }
    }
    
    public function preFlush(EventArgs $args) {
        $this->post_flush_persist_counter = 0 ;
    }
    
    public function postFlush(EventArgs $args) {
        if( $this->post_flush_persist_counter ) {
            $om    = $args->getEntityManager() ;
            $om->flush() ;
        }
    }
    
    private function onEvent(\Doctrine\Common\Persistence\ObjectManager $om, $object, $event ) {

        $className = get_class($object);
        if (!isset($this->configs[$className])) {
            return ;
        }
        
        $conf = & $this->configs[$className] ;

        if ( self::preUpdate === $event ) {
            if( isset($conf['uuid'])  ) {
                $uow = $om->getUnitOfWork();
                $meta = $om->getClassMetadata($className);

                foreach ($conf['uuid'] as $property_name => $property_conf ) {
                    $property = $meta->getReflectionProperty($property_name);
                    $oldValue = $property->getValue($object);
                    if (null === $oldValue) {
                        $newValue = $this->uuid_generator->generate($om, $object);
                        $property->setValue($object, $newValue);
                        if ($object instanceof NotifyPropertyChanged) {
                            $uow->propertyChanged($object, $property_name, $oldValue, $newValue);
                        }
                    } 
                }
            }
            
        } else if ( self::onFlush === $event ) {
            
        } else if ( self::postUpdate === $event ) {
            if( isset($conf['file'])  ) {
                /**
                 * @var \Doctrine\ORM\Mapping\ClassMetadata
                 */
                $meta = $om->getClassMetadata($className) ;
                
                $object_id   = $meta->getReflectionProperty( $conf['id'] )->getValue($object) ;
                        
                foreach ($conf['file'] as $property_name => $property_conf ) {
                    $property = $meta->getReflectionProperty($property_name) ;
                    $oldValue = $property->getValue($object) ;
                    if ($oldValue) {
                        if( $object_id !== $oldValue->getEntityId() ) {
                            $oldValue->setEntityId( $object_id ) ;
                            $om->persist($oldValue) ;
                            $this->post_flush_persist_counter++ ;
                        }
                    }
                }
            }
            
            if( isset($conf['html'])  ) {
                
                $meta   = $om->getClassMetadata($className) ;
                $object_id  = $meta->getReflectionProperty( $conf['id'] )->getValue($object) ;
                $repo       = $om->getRepository('App\AdminBundle\Entity\File') ;
                
                foreach ($conf['html'] as $property_name => $property_conf ) {
                    $property = $meta->getReflectionProperty($property_name) ;
                    $oldValue = $property->getValue($object) ;
                    if ($oldValue) {
                        preg_match_all( self::HTML_PATTERN , $oldValue, $ms, PREG_SET_ORDER );
                        if( $ms ) foreach($ms as $ma) {
                            $file   = $repo->loadByUUID( $ma[1] ) ;
                            if(  
                                    $file  
                                    && $file->getIsHtmlFile() 
                                    && $object_id !== $file->getEntityId()
                            ) {
                                $file->setEntityId( $object_id ) ;
                                $om->persist($file) ;
                                $this->post_flush_persist_counter++ ;
                            } 
                        }
                    }
                }
            }
        
        } else if ( self::preRemove === $event ) {
            
             if( isset($conf['file'])  ) {
                 $meta = $om->getClassMetadata($className) ;
                 $object_id  = $meta->getReflectionProperty( $conf['id'] )->getValue($object) ;
                 foreach ($conf['file'] as $property_name => $property_conf ) {
                    $property = $meta->getReflectionProperty($property_name) ;
                    $oldValue = $property->getValue($object) ;
                    if (
                            $oldValue
                            && $object_id === $oldValue->getEntityId()
                            && $className === $oldValue->getClassName()
                            && $property_name === $oldValue->getPropertyName()
                            && $object_id === $oldValue->getEntityId()
                    ) {
                            $om->remove($oldValue) ;
                    }
                 }
            }
            
            if( isset($conf['html'])  ) {
                $meta   = $om->getClassMetadata($className) ;
                $object_id  = $meta->getReflectionProperty( $conf['id'] )->getValue($object) ;
                $repo       = $om->getRepository('App\AdminBundle\Entity\File') ;
                
                foreach ($conf['html'] as $property_name => $property_conf ) {
                    $property = $meta->getReflectionProperty($property_name) ;
                    $oldValue = $property->getValue($object) ;
                    if ($oldValue) {
                        preg_match_all( self::HTML_PATTERN , $oldValue, $ms, PREG_SET_ORDER );
                        if( $ms ) foreach($ms as $ma) {
                            $file   = $repo->loadByUUID( $ma[1] ) ;
                            if(  
                                    $file  
                                    && $file->getIsHtmlFile() 
                                    && $className === $file->getClassName()
                                    && $property_name === $file->getPropertyName()
                                    && $object_id === $file->getEntityId()
                            ) {
                                    $om->remove($file) ;
                            }
                       }
                    }
                }
            }
        }
    }

}