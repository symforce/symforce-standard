<?php

namespace App\AdminBundle\Compiler\MetaType\Action ;

class DeleteAction  extends AbstractAction {
    
    public $table = true ;
    
    public $icon = 'remove' ;
    public $template = 'AppAdminBundle:Admin:delete.html.twig' ;
    
    public function isRequestObject() {
        return true ;
    }
    
    public function isOwnerAuth(){
        return true ;
    }
    
    public function isWorkflowAuth(){
        return true ;
    }
} 