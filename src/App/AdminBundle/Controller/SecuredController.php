<?php

namespace App\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\Validator\Constraints as Asset ;

/**
 * @Route("/admin")
 */
class SecuredController extends Controller
{
    /**
     * @Route("/login", name="app_admin_login")
     * @Template()
     */
    public function loginAction(Request $request)
    {
        
        $form   = $this->crateForm($request) ;
        // $form   = $this->container->get('app.admin.loader')->getAdminByName('app_user')->getLoginForm( $request ) ;
        
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
     * @Route("/login_check", name="app_admin_check")
     */
    public function securityCheckAction()
    {
        throw new \RuntimeException('You must configure the check path to be handled by the firewall using form_login in your security firewall configuration.');
    }

    /**
     * @Route("/logout", name="app_admin_logout")
     */
    public function logoutAction()
    {
        throw new \RuntimeException('You must activate the logout in your security firewall configuration.');
    }
    
    
    
    protected function crateForm(\Symfony\Component\HttpFoundation\Request $request) {
        
        if ( $request->attributes->has(SecurityContext::AUTHENTICATION_ERROR) ) {
            $error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
        } else {
            $error = $request->getSession()->get(SecurityContext::AUTHENTICATION_ERROR);
            $request->getSession()->set( SecurityContext::AUTHENTICATION_ERROR , null ) ;
        }
        
        $tr = $this->container->get('translator') ;
        $app_domain  = $this->container->getParameter('app.admin.domain') ;
      
        $builder = $this->container->get('form.factory')->createNamedBuilder('login', 'form', array(
            'label'  => 'app.login.label' ,
            'translation_domain' => $app_domain ,
        )) ; 
        
        $builder
                    ->add('username', 'text', array(
                        'label' => 'app.login.username.label' ,
                        'translation_domain' => $app_domain ,
                        'data'  => $request->getSession()->get(SecurityContext::LAST_USERNAME) ,
                        'horizontal_input_wrapper_class' => 'col-xs-6',
                        'attr' => array(
                            'placeholder' => 'app.login.username.placeholder' ,
                        )
                    ) )
                    ->add('password', 'password', array(
                        'label'  => 'app.login.password.label' ,
                        'translation_domain' => $app_domain ,
                        'horizontal_input_wrapper_class' => 'col-xs-6',
                        'attr' => array(
                            
                        )
                    ) )
                
                    ->add('captcha', 'appcaptcha', array(
                        'label' => 'app.form.captcha.label' ,
                        'translation_domain' => $app_domain ,
                    ))
                
                ;
        $form     = $builder->getForm() ;
        
        if( $error ) {
            if( $error instanceof \Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException ) {
                $_error = new \Symfony\Component\Form\FormError( $tr->trans('app.login.error.crsf', array(), $app_domain ) ) ;
                $form->addError( $_error  ) ;
            } else if ( $error instanceof \App\UserBundle\Exception\CaptchaException ) {
                $_error = $tr->trans('app.login.error.captcha' , array(), $app_domain ) ;
                if( $this->container->getParameter('kernel.debug') ) {
                    $_error .= sprintf(" code(%s)",  $error->getCode()  ) ;
                }
                $_error = new \Symfony\Component\Form\FormError( $_error );
                $form->get('captcha')->addError( $_error ) ;
            } else if( $error instanceof \Symfony\Component\Security\Core\Exception\BadCredentialsException ) { 
                $_error = new \Symfony\Component\Form\FormError( $tr->trans('app.login.error.credentials' , array(), $app_domain ) ) ;
                $form->get('username')->addError( $_error ) ;
            }  else if( $error instanceof \Symfony\Component\Security\Core\Exception\DisabledException ) {
                $_error = new \Symfony\Component\Form\FormError( $tr->trans('app.login.error.disabled' , array(), $app_domain ) ) ;
                $form->get('username')->addError( $_error ) ;
            } else {
                $_error = new \Symfony\Component\Form\FormError( $error->getMessage() ) ;
                if( $this->container->getParameter('kernel.debug') ) {
                    \Dev::dump(  $error ) ;
                }
                $form->get('username')->addError( $_error ) ;
            }
        }
        
        return $form ;
    }
}
