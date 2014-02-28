<?php

namespace App\AdminBundle\Event;
 
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class FormSubscriber implements EventSubscriberInterface
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;
    
    private $is_xhr_request ;

    public static function getSubscribedEvents()
    {
        return array(
            'app.event.form'     => array('onFormEvent', 0),
        );
    }
    
    public function setContainer(\Symfony\Component\DependencyInjection\ContainerInterface $container) {
        $this->container = $container ;
    }
    
    public function isXhrRequest() {
        return $this->is_xhr_request ;
    }

    public function onFormEvent(FormEvent $event)
    {
        if( !isset($_POST['app_validate_element']) ) {
            return ;
        }
        $form_element_name  = $_POST['app_validate_element'] ;
        if( !preg_match_all('/\[(.+?)\]/', $form_element_name , $matches ) ) {
            return ;
        }
        
        $this->is_xhr_request   = true ;
        
        $elements   = $matches[1] ;
        $element    = $elements[0] ;
        $request    = $event->getRequest() ;
        $form       = $event->getForm() ;
        
        $json   = array(
            'errno' => null ,
            'element' => $element ,
            'error' => array() ,
        );
        
        if( $form->has($element) ) {
            $form->bind($request);
            $json['errno']  = __LINE__ ;
            $json['valid']  = $form->isValid() ;
            
            if( !$json['valid'] ) {
                $errors = array() ;
                $this->getErrors($form->get($element), $errors );
                
                $json['error'] = array_values( $errors ) ;
            }
            
        } else {
            $json['error']  = 'not find' ;
            $json['errno']  = __LINE__ ;
        }
        
        $response   = new \Symfony\Component\HttpFoundation\JsonResponse($json) ;
        $event->setResponse($response) ;
    }
    
    public function getErrors(\Symfony\Component\Form\Form $element, array & $errors){
         // $path  = $element->getPropertyPath()->__toString() ;
         foreach ($element->getErrors() as $error) {
            $errors[]   = $error->getMessage() ;
         }
         foreach($element->all() as $child){
             $this->getErrors($child, $errors) ;
         }
    }

}