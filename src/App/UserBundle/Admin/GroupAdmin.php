<?php

namespace App\UserBundle\Admin ;

use Symfony\Bundle\FrameworkBundle\Controller\Controller ;
use Symfony\Component\HttpFoundation\Request ;
use App\AdminBundle\Compiler\Cache\ActionCache ;
use Symfony\Component\Form\Form ;


abstract class GroupAdmin extends \App\AdminBundle\Compiler\Cache\AdminCache {
    
    public function onUpdate(Controller $controller, Request $request, ActionCache $action, $object, Form $form ){
        if( $object->default_group || $object->trust_group ) {
            $roles  = $this->admin_loader->getRoleHierarchy() ;
            
            foreach($object->getRoles() as $role ) {
                if( 'ROLE_ADMIN' === $role || isset($roles[$role]) && in_array( 'ROLE_ADMIN' , $roles[$role] ) ){
                    $form->addError( new \Symfony\Component\Form\FormError( 
                                $this->trans(".form.default_group.no_admin") 
                            ) );
                    return ;
                }
            }
            
            if( $object->hasRole('ROLE_ADMIN') ) {
                
            }
            if( $object->default_group && $object->trust_group ) {
                $form->addError( new \Symfony\Component\Form\FormError( 
                            $this->trans(".form.default_group.trust_default_conflict") 
                        ) );
                return ;
            }
            $em   = $this->getManager() ;
            $repo   = $this->getRepository() ;
            $groups = $repo->findAll();
            foreach($groups as $group) {
                
                if( $group->isEqual($object)  ) {
                    continue ;
                }
                
                if( $object->default_group ) {
                    if( $group->default_group ) {
                        $group->default_group   = false ;
                        $em->persist($group) ;
                    }
                }
                
                if( $object->trust_group ) {
                    if( $group->trust_group ) {
                        $group->trust_group   = false ;
                        $em->persist($group) ;
                    }
                }
            }
        }
        parent::onUpdate($controller, $request, $action, $object, $form );
    }
    
}
