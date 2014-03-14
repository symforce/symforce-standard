<?php

namespace App\AdminBundle\Compiler\Cache;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\Form\FormBuilder ;
use Symfony\Component\Form\FormFactory ;
use Symfony\Component\Form\ResolvedFormTypeFactory ;
use Symfony\Component\Form\ResolvedFormType ;
// form.type, form.type_extension, form.type_guesse

abstract class CreateActionCache extends ActionCache {
    
    final public function isCreateAction() {
        return true ;
    }
    
    public function isFormAction(){
        return true ;
    }
    
    public function onController(Controller $controller, Request $request){
       
        $object = $this->admin->newObject() ;
        
        $label  = null ;
        if( $this->admin->tree && $this->admin->getTreeObjectId() ) {
            $label = $this->admin->trans('app.tree.create.title', array(
                        '%object%' => $this->admin->string(  $this->admin->getTreeObject() ) ,
                        '%admin%' => $this->admin->getLabel() ,
                       ) , $this->app_domain 
                   ) ;
        } else {
            $label = $this->admin->getFormLabel() ;
        }
        
        $list_url = $this->admin->path('list') ;
        
        $this->admin->setFormOriginalObject($object) ;
        $builder  = $controller->createFormBuilder( $object,  array(
            'label' => $label ,
        )) ; 
        
        $this->admin->buildCreateForm($controller, $object, $builder, $this ) ;
        $this->buildFormReferer($request, $builder, $object, $list_url);
        $form     = $builder->getForm() ;
        $this->setForm($form);
        
        if( $request->isMethod('POST') ) {
             $form->handleRequest($request); 
             if ($form->isValid()) {
                  $this->admin->onUpdate($controller, $request, $this, $object, $form ) ;
                  if ($form->isValid()) {
                        $this->admin->update( $object ) ;
                        return $this->admin->afterUpdate($controller, $request, $this, $object, $form) ;
                  }
             } 
        }

        return $controller->render( $this->template , array(
            'apploader' =>  $controller->get('app.admin.loader')  ,
            'admin' => $this->admin ,
            'action' => $this ,
            'form'  => $form->createView() ,
        ) );
    }
    
}
