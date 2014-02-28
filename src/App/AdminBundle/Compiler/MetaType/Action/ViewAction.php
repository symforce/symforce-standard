<?php

namespace App\AdminBundle\Compiler\MetaType\Action ;

class ViewAction  extends AbstractAction {
    
    public $property_annotation_class_name = 'App\AdminBundle\Compiler\Annotation\View' ;
    public $template = 'AppAdminBundle:Admin:view.html.twig' ;
    
    public $_groups = array() ;

    public function isRequestObject() {
        return true ;
    }
    
    public function isCreateTemplate(){
        return true ;
    }
    
    public function isWorkflowAuth(){
        return true ;
    }
    
    public function isPropertyAuth(){
        return true ;
    }
    
    public function isViewAction() {
        return true ;
    }
    
    public function addProperty( $property, \App\AdminBundle\Compiler\Annotation\Annotation $annot ){
        $_property  = new ViewProperty($this->children , $this->admin_object, $property, $annot ) ;
    }
    
    /**
     * @return \App\AdminBundle\Compiler\Generator\PhpClass
     */
    public function compile(){
        parent::compile() ;
        
        // $this->children->properties
        $rc = $this->admin_object->reflection ;
        
            
        $admin_class    = $this->admin_object->getCompileClass() ;
        $class = $this->getCompileClass();
        
        $twig_writer  = $this->_twig->getWriter() ;
        // generate twig template
        $twig_writer
                ->writeln('{% set _object = object %}' )
                ->writeln('{% block content_view %}' )
                ->writeln('<div class="form-horizontal">')
                ->indent()
        ;
        
        $children   = array() ;
      
        foreach($this->admin_object->form->children->properties as $property_name => $_property  ) {
            if( isset($this->children->properties[$property_name]) ) {
                $property   = $this->children->properties[$property_name] ;
                $property->_annot   = true ;
            } else {
                $property   = new ViewProperty($this->children, $this->admin_object, $property_name) ;
                $property->lazyInitialize() ;
                $children[$property_name] = $property ;
            }
            if( null !== $_property->group && null === $property->group ) {
                $property->set_group( $_property->group ) ;
            }
            
            if( null !== $_property->position && null === $property->position ) {
                $property->set_position( $_property->position ) ;
            }
        }
        
        foreach( $rc->getProperties() as $p ) {
            if( $p->isStatic() ) {
                continue ;
            }
            $property_name  = $p->getName() ;
            if( isset($children[$property_name]) ) {
                continue ;
            }
            if( isset($this->children->properties[$property_name]) ) {
                $property   = $this->children->properties[$property_name] ;
            } else {
                $property   = new ViewProperty($this->children, $this->admin_object, $property_name) ;
                $property->lazyInitialize() ;
                $property->_no_form_and_view    = true ;
            }
            
            $children[$property_name] = $property ;
        }
        
        
        $macro_writer   = new \App\AdminBundle\Compiler\Generator\PhpWriter() ;
        foreach($children as $property_name => $property ) {
            
            $label  = null ;
            if( $property->form_element ) {
                $label  = $property->form_element->label ;
            } else if ( isset($this->admin_object->action_collection->children['list']->children->properties[$property_name]) ) {
                $label  = $this->admin_object->action_collection->children['list']->children->properties[$property_name]->label ;
            } else {
                $label  = $property->label ;
            }
            
            $admin_twig_calss   = var_export( $this->admin_object->class_name ,1) ;
            $admin_twig_code    = sprintf('app_admin_class(%s)', $admin_twig_calss ) ;
            
            $admin_class->addLazyArray( 'properties_label',  $property_name  ,  array(
                $label->getPath() ,
                $label->getDomain() ,
            ) ) ;
            
            $macro_writer
                    ->write( '{% ' . sprintf('macro macro_label_%s(admin=false)', $property_name ) . ' %}' )
                    ->write('{% if admin is same as(false) %}{% set admin = ' .  $admin_twig_code . ' %}{% endif %}')  
                    ;
           if( $this->admin_object->_final_template ) {
                $macro_writer 
                        ->write( '{% ' . sprintf('import "%s" as parent_macro', $this->admin_object->_final_template ) . ' %}'  ) 
                        ->write('{% ' . sprintf('if twig_macro_exists(parent_macro, "macro_label_%s") ', $property_name). ' %}' ) 
                            ->indent()
                            ->write( '{{ ' . sprintf('parent_macro.macro_label_%s(admin)', $property_name). '}}' ) 
                            ->outdent()
                        ->write( '{% else %}') 
                            ->indent()
                            ->write( '{{' . $property->compileLabel() . ' }}'  )
                            ->outdent()
                        ->write( '{% endif %}') 
                       ;
            } else {
                $macro_writer->write( '{{ ' .  $property->compileLabel() . ' }}' )  ;
            }
            
            $macro_writer ->writeln( '{% endmacro %} ' )
                    ;
            
            $macro_writer
                    ->writeln( '{% ' . sprintf('macro macro_value_%s(_object, _property_value=false, admin=false)', $property_name ) . ' %}' )
                        ->indent()
                        ->writeln('{% if admin is same as(false) %}{% set admin = ' .  $admin_twig_code . ' %}{% endif %}')  
                        
                        ;
            
            if( $this->admin_object->generator->getParameter('kernel.debug') ) {
                $macro_writer->writeln( sprintf('{{ app_check_class(_object, %s) }}' ,$admin_twig_calss ));
            }
            
            $macro_writer->writeln('{% if _property_value is same as(false) %}{% set _property_value = ' .  $property->getTwigValue() . ' %}{% endif %}') ;
            
            if( $this->admin_object->_final_template ) {
                $macro_writer 
                        ->writeln( '{% ' . sprintf('import "%s" as parent_macro', $this->admin_object->_final_template ) . ' %}'  ) 
                        ->writeln('{% ' . sprintf('if twig_macro_exists(parent_macro, "macro_value_%s") ', $property_name). ' %}' ) 
                            ->indent()
                            ->writeln( '{{ ' . sprintf('parent_macro.macro_value_%s(_object, _property_value, admin)', $property_name). '}}' ) 
                            ->outdent()
                        ->writeln( '{% else %}') 
                            ->indent()
                            ->writeln( $property->compileValue() ) 
                            ->outdent()
                        ->writeln( '{% endif %}') 
                       ;
            } else {
                $macro_writer ->writeln(  $property->compileValue() )  ;
            }
                    
            $macro_writer->outdent()
                    ->writeln( '{% endmacro %} ' )
                    ;
            $macro_writer->write("\n");
        }
        
        
        if( $this->admin_object->workflow ) {
            $macro_writer
                    ->writeln('{% macro macro_workflow_report(admin=false) %}')
                    ->indent()
                    ->writeln('{% if admin is same as(false) %}{% set admin = ' .  $admin_twig_code . ' %}{% endif %}')  
                    ;
            $this->admin_object->workflow->compileListAction( $macro_writer ) ;
            $macro_writer
                    ->outdent() 
                    ->writeln('{% endmacro %}');
        }
        
        \Dev::write_file( $this->admin_object->_template_path, $macro_writer->getContent() );
        
        if( $this->admin_object->form->groups ) {
            foreach($this->admin_object->form->groups as $_group ) {
                $_annot = array(
                    'id'    => $_group->id  , 
                    'label' => $_group->label ,
                );
                if( $_group->name ) {
                    $_annot['name'] = $_group->name ;
                }
                
                if( null !== $_group->position ) {
                    $_annot['position'] = $_group->position ;
                }
                $this->_groups[ $_group->id ] = new ActionPropertyGroup( $_annot ) ;
            }
        }
        
        if( !isset($this->_groups['default']) ) {
            $this->_groups['default']    = new ActionPropertyGroup( 'default' ) ;
        }
        
        foreach($children as $property_name => $property ) {
            if( $property->form_element && 'apppassword' === $property->form_element->compile_form_type ) {
                continue ;
            }
            if( $property->_no_form_and_view ) {
                continue ;
            }
            if( $property->group ) {
                if( isset($this->_groups[ $property->group ]) ) {
                    $group  = $this->_groups[ $property->group ] ;
                } else {
                    $group   = new ActionPropertyGroup( $property->group ) ;
                    $this->_groups[  $property->group ] = $group ;
                }
            } else {
                $group  = $this->_groups[ 'default' ] ;
            }
            $group->add( $property->class_property , $property->position ) ;
        }
        
        foreach($this->_groups as $group ) {
            $group->fixLabel($this->admin_object->form->tr_node , $this->admin_object->generator->app_domain ) ;
            $group->sort() ;
        }
        
        
        $_anonymous_children   =  array() ;
        foreach($this->admin_object->_route_assoc->_anonymous_children as $child_admin_name => $child_properties ) {
            $child_admin    = $this->admin_object->generator->getAdminByName( $child_admin_name ) ;
            if( !$child_admin->_final_template ) {
                continue ;
            } 
            $_anonymous_children[ $child_admin_name ] = $child_properties ;
        }
        
        if( count($this->_groups) > 1 ) {
            foreach($this->_groups as $group) {
                if( ! count($group->properties) ) {
                    continue ;
                }
                $twig_writer
                        ->writeln( '{% set view_property_count = 0 %}' ) 
                        ->writeln('<fieldset class="view-group">')
                        ->indent() 
                        ;
                foreach($group->properties as $property_name ) {
                    $children[$property_name]->compileView( $twig_writer ) ;
                }
                $twig_writer
                        
                        ->write( '{% if view_property_count > 0 %}' ) 
                                ->write( '<legend class="view-group-header">' )
                                    ->write( $group->label->getTwigCode() )
                                ->write('</legend>')
                        ->writeln( '{% endif %}' ) 
                        
                        ->outdent()
                        ->writeln('</fieldset>')
                        ;
            }
            
            if( !empty($_anonymous_children) ) {
                $twig_writer
                        ->writeln( '{% set view_property_count = 0 %}' ) 
                        ->writeln('<fieldset class="view-group">')
                        ->indent() 
                        ;
                
                $this->compileAnonymousChildren($_anonymous_children,  $twig_writer ) ;
                
                $twig_writer
                        ->write( '{% if view_property_count > 0 %}' ) 
                                ->write( '<legend class="view-group-header">Others</legend>')
                        ->writeln( '{% endif %}' ) 
                        
                        ->outdent()
                        ->writeln('</fieldset>')
                        ;
            }
            
        } else {
            foreach($this->_groups as $group) {
                $twig_writer->writeln( '{% set view_property_count = 0 %}' ) ;
                foreach($group->properties as $property_name ) {
                    $children[$property_name]->compileView( $twig_writer ) ;
                }
            }
            $this->compileAnonymousChildren($_anonymous_children,  $twig_writer ) ;
        }
        
        $twig_writer
                ->writeln('{% block _admin_view_extra %}' )
                    
                ->writeln('{% endblock %}' )
                
               ->outdent()
               ->writeln('</div>' )
               ->writeln('{% endblock %}' )
        ;
        
        return $class ;
    }

    private function compileAnonymousChildren(array $_anonymous_children,  \App\AdminBundle\Compiler\Generator\PhpWriter $twig_writer){
        
        foreach($_anonymous_children as $child_admin_name => $child_properties ) {
            $child_admin    = $this->admin_object->generator->getAdminByName( $child_admin_name ) ;
            $twig_writer
                    ->writeln('{# if ' . sprintf('app_auth("%s", "list")', $child_admin->name ) .' #}')
                    ->indent()
                    ->writeln('{% ' . sprintf('import "%s" as child_macro', $child_admin->_final_template ) .' %}')
             ;
            foreach($child_properties as $child_property ) {
                $macro_name = 'admin_parent_' . $child_property ;
                $twig_writer
                        ->writeln('{% ' . sprintf('if twig_macro_exists(child_macro, "%s") ', $macro_name ). ' %}' )
                            ->writeln( '{% set view_property_count = view_property_count + 1 %}' )

                            ->writeln( '<div class="form-group">')
                            ->indent()

                            ->writeln( '<div class="control-label col-xs-3">')
                            ->indent()
                                ->writeln('{% ' . sprintf('if twig_macro_exists(child_macro, "%s_label") ', $macro_name ). ' %}' ) 
                                      ->indent()
                                      ->writeln( '{{ ' . sprintf('child_macro.%s_label(admin)', $macro_name ). '}}' ) 
                                      ->outdent()
                                 ->writeln( '{% else %}') 
                                    ->indent()
                                    ->writeln( $child_admin->label->getTwigCode() ) 
                                    ->outdent()
                                ->writeln( '{% endif %}') 
                            ->outdent()
                            ->writeln( '</div>')


                            ->writeln( '<div class="control-value col-xs-9">')
                                    ->writeln( '<td>') 
                                    ->indent()
                                        ->writeln( '{{ ' . sprintf('child_macro.%s( app_admin_class(%s), admin, _object)', $macro_name, var_export($child_admin->class_name,1) ). '}}' ) 
                                    ->outdent()
                                    ->writeln( '</td>')
                            ->writeln( '</div>')

                            ->outdent()
                            ->writeln( '</div>')
                        ->writeln( '{% endif %}') ;
            }

            $twig_writer
                    ->outdent()
                    ->writeln( '{# endif #}') ;
             ;
        }
    }
}

