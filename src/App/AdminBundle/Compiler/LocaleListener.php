<?php

namespace App\AdminBundle\Compiler ;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Symfony\Component\HttpFoundation\Request ;
use Symfony\Component\Form\FormFactory ;

use App\AdminBundle\Entity\Locale ;

class LocaleListener implements EventSubscriberInterface
{
  /**
   *
   * @var string
   */
  private $default_locale ;
  
  /**
   * @var array 
   */
  private $languages ;
  
  /**
   * @var FormFactory
   */
  private $form_factory ;
  
  public function __construct(FormFactory $form_factory, $locale, array $languages) {
        $this->form_factory = $form_factory ;
        $this->default_locale = $locale;
        $this->languages = $languages;
  }

  public function onKernelRequest(GetResponseEvent $event) {
        $request = $event->getRequest();
        if (!$request->hasPreviousSession()) {
            return;
        }
        $_locale    = $request->getSession()->get('app_locale', null ) ;
        if( !$_locale || !isset( $this->languages[$_locale] ) ) {
            $_locale    = $this->default_locale ;
            $request->getSession()->set('app_locale', $_locale );
        } 
        $request->setLocale( $_locale ) ;
   }
   
   public function getInlineForm(Request $request){
       
       $_locale = $request->getSession()->get( 'app_locale' , $this->default_locale ) ;
       $languages  = $this->languages ;
       
       if( 'POST' === $request->getMethod() ) {
            $languages = array(
                    $_locale => $this->languages[ $_locale ] ,
            );
       }
       
       return array(
           'locale' => $_locale ,
           'redirect_url'=> $request->getRequestUri() ,
           'languages'   => $languages ,
       );
   }
   
   public function getForm(Request $request, $inline = 0 ) {
       
    	$_locale = $request->getSession()->get( 'app_locale' , $this->default_locale ) ;
        $locale	= new Locale();
        $locale->setLocale( $_locale ) ;
        $locale->setRedirectUrl( $request->getRequestUri() ) ;
        
        if( !isset( $this->languages[$_locale]) ) {
            throw new \Exception( \sprintf('invalid langauge locale: `%s` , support langages: %s ', $_locale,  join(', ', array_keys( $this->languages ) ) ));
        }
        
        $languages  = $this->languages ;
        if( 'POST' === $request->getMethod() && $inline ) {
                $languages = array(
                        $_locale => $this->languages[ $_locale ] ,
                );
        }
        
        $form = $this->form_factory->createBuilder('form', $locale, array( 
                        'csrf_protection' => false ,
                ))
                 ->add(  'locale',  'choice',  array (
                                'choices' => $languages ,
                                'attr' => array(
                                    'class' => 'input-sm' ,
                                ) ,
                                'horizontal_input_wrapper_class'    => 'app_locale_select' ,
                            ) ) 
                    ->add('redirect_url',  'hidden' ) 
                    ->getForm()
                ;
        
        return $form ;
   }

   static public function getSubscribedEvents() {
        return array(
            // must be registered before the default Locale listener
            KernelEvents::REQUEST => array(array('onKernelRequest', 17)),
        );
   }
}