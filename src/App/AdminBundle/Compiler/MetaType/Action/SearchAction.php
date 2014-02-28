<?php

namespace App\AdminBundle\Compiler\MetaType\Action ;

class SearchAction  extends AbstractAction {
    
    public $property_annotation_class_name = 'App\AdminBundle\Compiler\Annotation\Filter' ;
    public $template = 'AppAdminBundle:Admin:search.html.twig' ;
    
    public function addProperty( $property, \App\AdminBundle\Compiler\Annotation\Annotation $annot ){
        $_property  = new SearchProperty($this->children , $this->admin_object, $property, $annot ) ;
    }
    
    /**
     * @return \App\AdminBundle\Compiler\Generator\PhpClass
     */
    public function compile(){
         
    }

}
