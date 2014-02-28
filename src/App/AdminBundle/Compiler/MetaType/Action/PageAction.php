<?php

namespace App\AdminBundle\Compiler\MetaType\Action ;

class PageAction  extends AbstractAction {
    
    public $table = true ;
    public $template = 'AppAdminBundle:Admin:page.html.twig' ;
    
    public function isRequestObject() {
        return true ;
    }
    
    public function isCreateTemplate(){
        return true ;
    }
    
    public function isPropertyAuth(){
        return true ;
    }
    
    public function addProperty( $property, \App\AdminBundle\Compiler\Annotation\Annotation $annot ){
        $this->throwError("can not add property:%s = %s ", $property, json_encode($annot) );
    }
    
    /**
     * @return \App\AdminBundle\Compiler\Generator\PhpClass
     */
    public function compile(){
        $class  = parent::compile() ;
        
        return $class ;
    }

}
