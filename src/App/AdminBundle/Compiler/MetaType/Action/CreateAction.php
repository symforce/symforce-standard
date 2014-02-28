<?php

namespace App\AdminBundle\Compiler\MetaType\Action ;

class CreateAction  extends AbstractAction {
    
    public $dashboard = true ;
    public $toolbar = true ;
    
    public $property_annotation_class_name = 'App\AdminBundle\Compiler\Annotation\Create' ;
    public $template = 'AppAdminBundle:Admin:create.html.twig' ;
    
    public function isCreateTemplate(){
        return true ;
    }
    
    public function isCreateForm(){
        return true ;
    }
    
    public function isOwnerAuth(){
        return false ;
    }
    
    public function isCreateAction() {
        return true ;
    }
    
    public function addProperty( $property, \App\AdminBundle\Compiler\Annotation\Annotation $annot ){
        $_property  = new CreateProperty($this->children , $this->admin_object, $this, $property, $annot ) ;
    }
    
    /**
     * @return \App\AdminBundle\Compiler\Generator\PhpClass
     */
    public function compile(){
        $class  = parent::compile() ;
        
        $twig_writer  = $this->_twig->getWriter() ;
        $twig_writer
                ->writeln('{% block action_form_scripts %}')
                ;
        if( $this->admin_object->_final_template ) {
                $twig_writer 
                        ->writeln( '{% ' . sprintf('import "%s" as admin_%s_macro', $this->admin_object->_final_template,  $this->admin_name  ) . ' %}'  ) 
                        ->writeln('{% ' . sprintf('if twig_macro_exists(admin_%s_macro, "action_form_scripts") ',  $this->admin_name ). ' %}' ) 
                            ->indent()
                            ->writeln( '{{ ' . sprintf('admin_%s_macro.action_form_scripts(admin,action,form)', $this->admin_name ). '}}' ) 
                            ->outdent()
                        ->writeln( '{% endif %}') 
                       ;
        } 
        $twig_writer
                ->writeln('{% endblock %}')
                ;
        
        return $class ;
    }
}