<?php

namespace App\AdminBundle\Compiler\Cache;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;


/**
 * Description of PageActionCache
 *
 * @author loong
 */
class PageActionCache extends ActionCache  {
    
    public function isRequestObject() {
        return true ;
    }
    
    public function isPageAction() {
        return true ;
    }
    
    public function onController(Controller $controller, Request $request){
       
        $object_id  = (int) $this->admin->getRouteObjectId() ;
        $page_object    = null ;
        if( $object_id ) {
            $object = $this->admin->getRouteObject() ;
            $page_object = $this->admin->getPageObject( $object ) ;
        } else {
            $object = null ;
            $page_object = $this->admin->getRootPageObject() ;
        }
        
        $page_admin = $this->admin->getPageAdmin() ;
        
        $this->admin->setFormOriginalObject($object) ;
        $builder  = $controller->createFormBuilder($page_object, array(
            'label' => $this->admin->getFormLabel() ,
        )) ;
        
        $list_url = $this->admin->path('list') ;
                
        $update_action  = $page_admin->getAction('update') ;
        $page_admin->buildUpdateForm($controller, $page_object, $builder, $update_action ) ;
        $this->buildFormReferer($request, $builder, $object, $list_url);
        $form     = $builder->getForm() ;
        $this->setForm($form);
        
        if( $request->isMethod('POST') ) {
             $form->bind($request);
             if ($form->isValid())  {
                 
                $page_admin->onUpdate($controller, $request, $update_action, $page_object, $form ) ;
                
                if ($form->isValid()) {
                    
                    $em = $this->admin->getManager() ;
                    $uow  = $em->getUnitOfWork();

                    if( $object ) {
                        // need copy propertie back to object , 
                        $this->admin->update( $object ) ;
                    }  else {
                        $page_admin->update( $page_object ) ;
                    }

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
            'page_admin' => $page_admin ,
            'action' => $this ,
            'object' => $object ,
            'form'  => $form->createView() ,
        ) );
    }
}
