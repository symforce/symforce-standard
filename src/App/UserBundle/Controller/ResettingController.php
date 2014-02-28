<?php

namespace App\UserBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;


use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use FOS\UserBundle\Model\UserInterface;

/**
 * @Route("/resetting")
 */
class ResettingController extends Controller { // \FOS\UserBundle\Controller\ResettingController
    
    /**
     * Request reset user password: show form
     * 
     * @Route("/request", name="fos_user_resetting_request", methods="GET|POST")
     * @Template
     */
    public function requestAction(Request $request)
    {
        
        $tr = $this->container->get('translator') ;
        $domain = 'AppUserBundle' ;
        $app_domain  = $this->container->getParameter('app.admin.domain') ;
      
        $builder = $this->container->get('form.factory')->createNamedBuilder('form', 'form', null, array(
            'label'  => 'app_user.resetting.title' ,
            'translation_domain' => $domain ,
        )) ; 
        
        $builder
                    ->add('username', 'text', array(
                        'label' => 'app.login.username.label' ,
                        'translation_domain' => $app_domain ,
                        'input_width'   => 46 ,
                        'attr' => array(
                            'placeholder' => 'app.login.username.placeholder' ,
                        ),
                        'constraints'   => array(
                            new \Symfony\Component\Validator\Constraints\Callback(function($username, \Symfony\Component\Validator\ExecutionContext $context ) use($tr){
                                $user   = $this->container->get('fos_user.user_manager')->findUserByUsernameOrEmail($username) ;
                                if( !$user ) {
                                    $context->addViolation( $tr->trans('resetting.request.invalid_username', array(
                                        '%username%'    => $username ,
                                    ), 'FOSUserBundle') );
                                } else if ($user->isPasswordRequestNonExpired($this->container->getParameter('fos_user.resetting.token_ttl')) ) {
                                    if( ! $this->container->getParameter('kernel.debug') ) {
                                        // $context->addViolation( $tr->trans('resetting.password_already_requested', array(), 'FOSUserBundle') );
                                    }
                                }
                            }) ,
                        ) ,
                    ) )
                
                    ->add('captcha', 'appcaptcha', array(
                        'label' => 'app.form.captcha.label' ,
                        'translation_domain' => $app_domain ,
                    ))
                
                ;
        
        $form     = $builder->getForm() ;
        
        $dispatcher = $this->container->get('event_dispatcher');
        $event = new \App\AdminBundle\Event\FormEvent($form, $request);
        $dispatcher->dispatch('app.event.form', $event) ;
        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }
        
        if ('POST' === $request->getMethod()) {
            $form->bind($request);
            if ($form->isValid()) {
                
                $user   = $this->container->get('fos_user.user_manager')->findUserByUsernameOrEmail( $form->get('username')->getData() ) ;
                
                if (null === $user->getConfirmationToken()) {
                    /** @var $tokenGenerator \FOS\UserBundle\Util\TokenGeneratorInterface */
                    $tokenGenerator = $this->container->get('fos_user.util.token_generator');
                    $user->setConfirmationToken($tokenGenerator->generateToken());
                }
                
                $this->container->get('fos_user.mailer')->sendResettingEmailMessage($user);
                $user->setPasswordRequestedAt(new \DateTime());
                $this->container->get('fos_user.user_manager')->updateUser($user);

                $email  = $this->getObfuscatedEmail($user) ;
                
                return $this->container->get('templating')->renderResponse('AppUserBundle:Resetting:checkEmail.html.twig', array(
                    'email' => $email,
                )) ;
            }
        }
        return array(
            'form'  => $form->createView() ,
        ) ;
    }

    /**
     * Tell the user to check his email provider
     * 
     * @Route("/check-email", name="fos_user_resetting_check_email", methods="GET")
     * @Template("AppUserBundle:Resetting:checkEmail.html.twig")
     */
    public function checkEmailAction(Request $request)
    {
        $email = $request->query->get('email');

        if (empty($email)) {
            // the user does not come from the sendEmail action
            return new RedirectResponse($this->container->get('router')->generate('fos_user_resetting_request'));
        }

        return array(
            'email' => $email,
        ) ;
    }

    /**
     * Reset user password
     * @Route("/reset/{token}", name="fos_user_resetting_reset", methods="GET|POST")
     * @Template
     */
    public function resetAction(Request $request, $token)
    {
        /** @var $userManager \FOS\UserBundle\Model\UserManagerInterface */
        $userManager = $this->container->get('fos_user.user_manager');
        /** @var $dispatcher \Symfony\Component\EventDispatcher\EventDispatcherInterface */
        $dispatcher = $this->container->get('event_dispatcher');

        $user = $userManager->findUserByConfirmationToken($token);

        if (null === $user) {
            throw new NotFoundHttpException(sprintf('The user with "confirmation token" does not exist for value "%s"', $token));
        }

        $event = new GetResponseUserEvent($user, $request);
        $dispatcher->dispatch(FOSUserEvents::RESETTING_RESET_INITIALIZE, $event);

        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }
        
        $tr = $this->container->get('translator') ;
        $domain = 'AppUserBundle' ;
        $app_domain  = $this->container->getParameter('app.admin.domain') ;
        
        $builder = $this->container->get('form.factory')->createNamedBuilder('form', 'form', $user, array(
            'label'  => 'app_user.resetting.title' ,
            'translation_domain' => $domain ,
        )) ; 
        
        $builder
                   
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
                ->add('captcha', 'appcaptcha', array(
                    'label' => 'app.form.captcha.label' ,
                    'translation_domain' => $app_domain ,
                ))
                
                ;
        
        $form     = $builder->getForm() ;
        
        $event = new \App\AdminBundle\Event\FormEvent($form, $request);
        $dispatcher->dispatch('app.event.form', $event) ;
        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }
        
        if ('POST' === $request->getMethod()) {
            $form->bind($request);

            if ($form->isValid()) {
                $event = new FormEvent($form, $request);
                $dispatcher->dispatch(FOSUserEvents::RESETTING_RESET_SUCCESS, $event);

                $userManager->updateUser($user);

                if (null === $response = $event->getResponse()) {
                    $url = $this->container->get('router')->generate('fos_user_profile_show');
                    $response = new RedirectResponse($url);
                }
                
                $dispatcher->dispatch(FOSUserEvents::RESETTING_RESET_COMPLETED, new FilterUserResponseEvent($user, $request, $response));
                
                return $response;
            }
        }

        return array(
            'token' => $token,
            'form' => $form->createView(),
        ) ;
    }

    /**
     * Get the truncated email displayed when requesting the resetting.
     *
     * The default implementation only keeps the part following @ in the address.
     *
     * @param \FOS\UserBundle\Model\UserInterface $user
     *
     * @return string
     */
    protected function getObfuscatedEmail(UserInterface $user)
    {
        $email = $user->getEmail();
        if (false !== $pos = strpos($email, '@')) {
            $email = '...' . substr($email, $pos);
        }

        return $email;
    }
}
