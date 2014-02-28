<?php

namespace App\AdminBundle\Compiler\Cache;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Description of ListAction
 *
 * @author loong
 */
class ListActionCache extends ActionCache {
    
    protected $action_template ;
    
    public function getActionTemplate(){
        return $this->action_template ;
    }
    
    public function isListAction() {
        return true ;
    }
    
    public function isPropertyAction() {
        return true ;
    }
    
    public function isWorkflowAction() {
        return true ;
    }
    
    protected $page_number = 1 ;
    
    public function setPageNumber( $page ) {
        $this->page_number  = $page ;
    }

    public function onController(Controller $controller, Request $request){
       
        $repos  = $this->admin->getRepository();
        
        $dql    = $this->admin->getListDQL();
        $em     = $this->admin->getManager();
        $query  = $em->createQuery($dql);

        $paginator  = $this->admin->getService('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $this->page_number ,
            10 ,
            array(
                'pageParameterName' => 'admin_list_page' ,
            )
        );
        
        return $controller->render( $this->template , array(
            'apploader' =>  $controller->get('app.admin.loader') , 
            'admin' => $this->admin ,
            'action' => $this ,
            'pagination' => $pagination ,
        ) );
    }
}
