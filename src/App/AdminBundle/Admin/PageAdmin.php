<?php

namespace App\AdminBundle\Admin ;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use App\AdminBundle\Compiler\Cache\ActionCache ;
use Symfony\Component\Form\Form ;


/**
 * Description of PageAdmin
 *
 * @author loong
 */
abstract class PageAdmin extends \App\AdminBundle\Compiler\Cache\AdminCache {
    
    public function auth( $action, $page = null ){
        $pass   = parent::auth($action, $page) ;
        if( $pass && $page ) {
            if(  $page->admin_class && $this->admin_loader->hasAdminClass( $page->admin_class ) ) {
                $admin  = $this->admin_loader->getAdminByClass( $page->admin_class ) ;
                $_action   = $this->getAction( $action ) ;
                if( $_action->isDeleteAction() || $_action->isCreateAction() ) {
                    return false ;
                }
                
                if( $page->admin_entity_id  ) {
                    $object = $admin->getObjectById( $page->admin_entity_id ) ;
                    if( $object ) {
                        return $_action->isFormAction() ? $admin->auth('page', $object) :   $admin->auth($action , $object)  ;
                    } else {
                        throw new \Exception( sprintf("invalid Page(id=%s)", $page->getId()) );
                    }
                } else if ( $page->admin_is_root ) {
                    return $_action->isFormAction() ? $admin->auth('page') :   $admin->auth($action)  ;
                } else {
                    throw new \Exception( sprintf("invalid Page(id=%s)", $page->getId()) );
                }
            }
        }
        return $pass ;
    }
    
    public function onUpdate(Controller $controller, Request $request, ActionCache $action, $page , Form $form ){
        if( $page->admin_entity_id &&  $this->admin_loader->hasAdminClass( $page->admin_class ) ) {
            $admin  = $this->admin_loader->getAdminByClass( $page->admin_class ) ;
            $object = $admin->getObjectById( $page->admin_entity_id ) ;
            if( $object ) {
                $_page  = $admin->getReflectionProperty( $admin->property_page_name )->getValue( $object ) ;
                if( $_page !== $page ) {
                    throw new \Exception("bigger error");
                }
                
                $has_changed    = false ;
                $config = $admin->copy_properties[ $admin->property_page_name ] ;
                foreach($config as $object_property => $page_property ) {
                    $page_value = $this->getReflectionProperty( $page_property )->getValue($page) ;
                    $object_value = $admin->getReflectionProperty( $object_property )->getValue($object) ;
                    if( $object_value !== $page_value ) {
                        $admin->getReflectionProperty( $object_property )->setValue($object, $page_value ) ;
                        $has_changed    = true ;
                    }
                }
                if( $has_changed ) {
                    $em = $admin->getManager() ;
                    $em->persist( $object ) ;
                }
            }
        }
        parent::onUpdate($controller, $request, $action, $page, $form );
    }
    
}
