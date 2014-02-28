<?php

namespace App\UserBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Security\Core\SecurityContext;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Route()
 * \FOS\UserBundle\Controller\SecurityController 
 */
class SecurityController extends Controller
{
    /**
     * @Route("/login", name="fos_user_security_login")
     * @Template()
     */
    public function loginAction(\Symfony\Component\HttpFoundation\Request $request)
    {
        $form   = $this->container->get('app.admin.loader')->getAdminByName('app_user')->getLoginForm( $request ) ;
        
        $dispatcher = $this->container->get('event_dispatcher');
        $event = new \App\AdminBundle\Event\FormEvent($form, $request);
        $dispatcher->dispatch('app.event.form', $event) ;
        if (null !== $event->getResponse()) {
            return $event->getResponse() ;
        } 
      
        return array(
            'form'  => $form->createView() ,
        );
    }

    /**
     * @Route("/login_check", name="fos_user_security_check")
     */
    public function checkAction()
    {
        throw new \RuntimeException('You must configure the check path to be handled by the firewall using form_login in your security firewall configuration.');
    }

    /**
     * @Route("/logout", name="fos_user_security_logout")
     */
    public function logoutAction()
    {
        throw new \RuntimeException('You must activate the logout in your security firewall configuration.');
    }

}
