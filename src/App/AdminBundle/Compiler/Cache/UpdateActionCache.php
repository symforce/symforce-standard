<?php

namespace App\AdminBundle\Compiler\Cache;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;

/**
 * Description of UpdateAction
 *
 * @author loong
 */
abstract class UpdateActionCache extends ActionCache  {
    
    public function isRequestObject() {
        return true ;
    }
    
    public function isWorkflowAction() {
        return true ;
    }
    
    public function isFormAction() {
        return true ;
    }
    
    // abstract protected function createForm(FormBuilder $builder) ;
    
    public function onController(Controller $controller, Request $request){
       
        $object = $this->admin->getRouteObject() ;
        $list_url   = $this->admin->path('list') ;
        
        if( ! $object ) {
            $request->getSession()->getFlashBag()->add('error', 'not exists!' ) ;
            return $controller->redirect( $list_url ) ;
        }
        
        $tr = $controller->get('translator') ;
        
        $builder  = $controller->createFormBuilder($object, array(
            'label' => $this->admin->getFormLabel() ,
        )) ;
        
        $this->admin->buildUpdateForm($controller, $object, $builder, $this ) ;
        $this->buildFormReferer($request, $builder, $object, $list_url);
        $form  = $builder->getForm() ;
        
        if( $request->isMethod('POST') ) {
             $form->bind($request);
             if ($form->isValid())  {
                
                $this->admin->onUpdate($controller, $request, $this, $object, $form ) ;
                if ( $form->isValid() )  {
                    $em = $this->admin->getManager() ;
                    $uow  = $em->getUnitOfWork();
                    $this->admin->update( $object ) ;
                    $request->getSession()->getFlashBag()->add('info',
                                $this->trans( 'app.action.update.finish' , $object )
                            ) ;
                    return $controller->redirect( $this->getFormReferer($form) ) ;
                }
             }
        }
        
        return $controller->render( $this->template , array(
            'apploader' =>  $controller->get('app.admin.loader') , 
            'admin' => $this->admin ,
            'action' => $this ,
            'form'  => $form->createView() ,
        ) );
    }

}

