<?php

namespace App\UserBundle\Listener ;
 
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use FOS\UserBundle\FOSUserEvents;

class FosUserSubscriber implements EventSubscriberInterface
{
    
        
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
    
    public static function getSubscribedEvents()
    {
        return array(
            FOSUserEvents::REGISTRATION_CONFIRM => array('onUserConfirm', 0),
            FOSUserEvents::REGISTRATION_SUCCESS => array('onUserCreated', 0),
        );
    }
 
    
    public function onUserCreated(\FOS\UserBundle\Event\FormEvent $event) {
        $form   = $event->getForm() ;
        $user   = $form->getData() ;
        $request  = $event->getRequest() ;
        $admin = $this->container->get('app.admin.loader')->getAdminByClass( get_class($user) ) ;
        $admin->setUserRegistration($user, $request);
    }

    public function onUserConfirm(\FOS\UserBundle\Event\GetResponseUserEvent $event)
    {
        $user   = $event->getUser() ;
        $request  = $event->getRequest() ;
        
        $log    = new \App\UserBundle\Entity\UserLog() ;
        $log->setUser($user) ;
        $log->setType( \App\UserBundle\Entity\UserLog::TYPE_USER_CONFIRM ) ;
        
        $log_admin  = $this->container->get('app.admin.loader')->getAdminByClass($log) ;
        $log_admin->initByRequest($log, $request) ;
        $em     = $log_admin->getManager() ;
        $em->persist( $log ) ;
        $em->flush() ;
    }
    
}
