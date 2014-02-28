<?php

namespace App\AdminBundle\Compiler\Cache;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Description of DeleteAction
 *
 * @author loong
 */
class DeleteActionCache extends ActionCache {
    
    public function isRequestObject() {
        return true ;
    }
    
    public function isDeleteAction() {
        return true ;
    }
    
    public function isOwnerAction() {
        return true ;
    }
    
    public function isPropertyAction() {
        return false ;
    }
    
    public function onController(Controller $controller, Request $request){
        
        $object = $this->admin->getRouteObject() ;
        if( ! $object ) {
            $request->getSession()->getFlashBag()->add('error', 'not exists!' ) ;
            return $controller->redirect( $this->admin->path('list') ) ;
        }
        
        $admin_children = array() ;
        $children = $this->admin->getAdminRouteChildren() ;
        if($children) foreach($children as $child_admin_name => $o ) {
            if( $o[0] ) {
                throw new \Exception("unimplement") ;
            }
            $admin_children[ $child_admin_name] = array() ;
            $child_admin    = $this->admin->getAdminLoader()->getAdminByName( $child_admin_name ) ;
            $properties     = $o[0] ? $o[1] : array( $o[1] ) ;
            foreach($properties  as $config ) {
                $child_property = $config[0] ;
                $my_property = $config[1] ;
                $count  = $child_admin->countBy( $child_property, $object );
                $admin_children[ $child_admin_name][ $child_property ] = $count ;
            }
        }
        
        /**
         * @var \Symfony\Component\Form\FormBuilder
         */
        $builder    = $controller->createFormBuilder( $object , array(
            'label' => $this->admin->getFormLabel() ,

            'constraints'   => array(
                new \Symfony\Component\Validator\Constraints\Callback(function($object, \Symfony\Component\Validator\ExecutionContext $context ) use($controller, $admin_children ){
                    
                    foreach($admin_children as $child_admin_name => $list ) {
                        $child_admin    = $this->admin->getAdminLoader()->getAdminByName( $child_admin_name ) ;
                        foreach($list  as $count ) {
                            if( $count > 0 ) {
                                if( !$child_admin->auth('delete') ) {
                                    $error   = $this->admin->trans('app.action.delete.error.child', array(
                                        '%admin%'    => $this->admin->getLabel() ,
                                        '%child%'    => $child_admin->getLabel() ,
                                        '%count%'    => $count ,
                                    ), $this->app_domain );
                                    $context->addViolation( $error) ;
                                } 
                            }
                        }
                    }
                    
                }) ,
            ) ,
        )) ;
        
        $form     = $builder->getForm() ;
        if( $request->isMethod('POST') ) {
             $form->bind($request);
             if ($form->isValid())  {
                
                $msg = $this->trans( 'app.action.delete.finish' , $object ) ;
                $this->admin->remove( $object ) ;
                $request->getSession()->getFlashBag()->add('info', $msg ) ;

                return $controller->redirect( $this->admin->path('list') ) ;
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
