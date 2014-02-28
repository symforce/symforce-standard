<?php

namespace App\UserBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\Model\UserInterface;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route("/profile")
 */
class ChangePasswordController extends \FOS\UserBundle\Controller\ChangePasswordController {
    
    /**
     * @Route("/change-password", name="fos_user_change_password")
     * Template("AppWebBundle:Profile:changePassword.html.twig")
     */
    public function changePasswordAction(Request $request)
    {
        $user = $this->container->get('security.context')->getToken()->getUser();
        if (!is_object($user) || !$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        /** @var $dispatcher \Symfony\Component\EventDispatcher\EventDispatcherInterface */
        $dispatcher = $this->container->get('event_dispatcher');

        $event = new GetResponseUserEvent($user, $request);
        $dispatcher->dispatch(FOSUserEvents::CHANGE_PASSWORD_INITIALIZE, $event);

        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }

        $admin = $this->container->get('app.admin.loader')->getAdminByClass( $user ) ;
         
        $form = $this->createFrom($user, $admin ) ;
        $event = new \App\AdminBundle\Event\FormEvent($form, $request);
        $dispatcher->dispatch('app.event.form', $event) ;
        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }

        if ($request->isMethod('POST')) {
            $form->bind($request);

            if ($form->isValid()) {
                /** @var $userManager \FOS\UserBundle\Model\UserManagerInterface */
                $userManager = $this->container->get('fos_user.user_manager');

                $event = new FormEvent($form, $request);
                $dispatcher->dispatch(FOSUserEvents::CHANGE_PASSWORD_SUCCESS, $event);

                $userManager->updateUser($user);

                if (null === $response = $event->getResponse()) {
                    $url = $this->container->get('router')->generate('fos_user_profile_show');
                    $response = new RedirectResponse($url);
                }

                $dispatcher->dispatch(FOSUserEvents::CHANGE_PASSWORD_COMPLETED, new FilterUserResponseEvent($user, $request, $response));

                return $response;
            }
        }

        return  array('form' => $form->createView()) ;
    }
    
    
    private function createFrom($user, $admin){
         $domain    = $admin->getDomain() ;
         $app_domain    = $admin->getAppDomain() ;
         $tr     = $this->container->get('translator');
         
         $constraints   = array() ;
         
        if( !$this->container->getParameter('kernel.debug') ) {
            
        }
         
         $builder = $this->container->get('form.factory')->createBuilder( 'form', $user , array(
             'show_legend' => false ,
             'constraints' => $constraints ,
         ));
         
         $builder
            ->add('old_password', 'password', array(
                'label' => '当前密码' ,
                'input_width' => 60 ,
                'constraints' => array(
                    new \Symfony\Component\Security\Core\Validator\Constraints\UserPassword (array(
                        'message'   => "当前密码错误!", 
                    ))
                ),
            ))
            ->add('plainPassword', 'repeated', array(
                'type' => 'password' ,
                'options' => array(
                    'translation_domain' => $domain ,
                 ),
                'first_options' => array(
                        'label' => 'app_user.form.plainPassword.label' ,
                        'inline_help' => $tr->trans('app_user.form.plainPassword.help' ,  array(

                        ), $domain ) ,
                    ),
                'second_options' => array(
                        'label' => 'app_user.form.password_confirmation.label' ,
                        'input_width'   => false ,
                    ),
                'invalid_message' => 'fos_user.password.mismatch',
                'constraints' =>  array (
                         new \Symfony\Component\Validator\Constraints\NotBlank() ,
                         new \Symfony\Component\Validator\Constraints\Length(array("min" => 6, "max"=>"16" )),
                         new \App\UserBundle\Form\Constraints\Password(array(
                             'message'  => $tr->trans('app_user.form.plainPassword.error', array(), $domain) ,
                         )) ,
                     )
            )) 
            
           ;
            
        return $builder->getForm();
    }
    
    
}