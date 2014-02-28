<?php

namespace App\AdminBundle\Compiler\MetaType\Action ;

class ListAction  extends AbstractAction {
    
    public $property_annotation_class_name = 'App\AdminBundle\Compiler\Annotation\Table' ;
    public $template = 'AppAdminBundle:Admin:list.html.twig' ;
    public $dashboard = true ;
    public $toolbar = true ;
    
    /**
     * @var Html\Th 
     */
    public $tr ;
    
    public function set_table( $annot ) {
        $this->throwError("can not set table for ListAction");
    }
    
    public function set_tr( $annot ) {
        $this->tr    = new Html\Tr($annot) ;
    }
     
    public function isCreateTemplate(){
        return true ;
    }
    
    public function isPropertyAuth(){
        return true ;
    }
    
    public function isWorkflowAuth(){
        return true ;
    }
    
    public function isListAction() {
        return true ;
    }
    
    public function addProperty( $property, \App\AdminBundle\Compiler\Annotation\Annotation $annot ){
        
        $map  =  $this->admin_object->getPropertyDoctrineAssociationMapping( $property ) ;
        if( $map ) {
            $target_class   = $map['targetEntity'] ;
            if( $map['type'] === \Doctrine\ORM\Mapping\ClassMetadataInfo::ONE_TO_MANY ) {
                
                $target_property    = $map['mappedBy'] ;
                /**
                 * @todo maybe no need check because doctrine already check this
                 */
                if( !property_exists($target_class, $target_property) ) {
                    $this->throwError("Association class `%s` mapped property `%s`  for `%s` not exists!", $target_class, $target_property, $property );
                }

                $this->lazy_children[ $property ] [  $target_property ] = $annot ;
                
            } else if( $map['type'] === \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE ) {
                $target_property    = $map['inversedBy'] ;
                if( empty($target_property) ) {
                    // just a simple map , not sure how to handle it 
                    $this->lazy_children[ $property ][] = $annot ;
                } else {
                   if( !property_exists($target_class, $target_property) ) {
                       $this->throwError("Association class `%s` inversed property `%s` for `%s` not exists!", $target_class, $target_property, $property );
                   }
                   $this->lazy_children[ $property ] [ $target_property ] = $annot ;
                } 
                
            }  else if ( $map['type'] === \Doctrine\ORM\Mapping\ClassMetadataInfo::ONE_TO_ONE ) {
                $_meta   = $this->admin_object->generator->getMetadataForClass( $target_class ) ;
                if( $_meta->isIdentifierComposite ) {
                    // @TODO add Composite route handle
                    echo "\n", __FILE__, "\n", __LINE__, "\n" ; exit ;
                }
                $target_property = $_meta->getSingleIdentifierFieldName() ;  
                $this->lazy_children[ $property ] [ $target_property ] = $annot ;
            }
            
        } else {
            $_property  = new ListProperty( $this->children , $this->admin_object, $property, $annot ) ;
        }
    }
    
    public function addParentProperty( $property, $target_property, \App\AdminBundle\Compiler\Annotation\Annotation $annot ){
        // add child route for admin object in this case
        // check map type should be OneToMany 
        $map        =  $this->admin_object->getPropertyDoctrineAssociationMapping( $property ) ;
        
        $target_class   = $map['targetEntity'] ;
        
        // to_one 
        if( $map['type'] === \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE ) {
            // find parent route  
            $parent_admin_object   = $this->admin_object->generator->getAdminByClass( $target_class ) ;
            $parent_admin_object->_route_assoc->addRouteChildren($target_property, $this->admin_object->name , $property);
            
        } else if( $map['type'] === \Doctrine\ORM\Mapping\ClassMetadataInfo::ONE_TO_MANY ) {
            // find child route
            $child_admin_object    = $this->admin_object->generator->getAdminByClass( $target_class ) ;
            $this->admin_object->_route_assoc->addRouteChildren($property, $child_admin_object->name , $target_property );
            
        } else if( $map['type'] === \Doctrine\ORM\Mapping\ClassMetadataInfo::ONE_TO_ONE ) {
            // find sibling entity
            $sibling_admin_object    = $this->admin_object->generator->getAdminByClass( $target_class ) ;
             
        }
        
        $_property  = new ListProperty( $this->children , $this->admin_object, $property, $annot ) ;
        
    }
    
    /**
     * @return \App\AdminBundle\Compiler\Generator\PhpClass
     */
    public function compile(){
        
        parent::compile() ;
        
        $_anonymous_children   =  $this->admin_object->_route_assoc->_anonymous_children ;
      
        $class = $this->getCompileClass();

        $twig_writer  = $this->_twig->getWriter() ;
        
        $twig_column_header = array() ;
        $twig_column_body = array() ;
        
        $class->addUseStatement('Symfony\Component\Form\FormBuilder') ;
        
        foreach($this->children->properties as $field_name => $property ) if( $property instanceof ListProperty ) {
            $twig_column_header[ $field_name ] = $property->compileTh() ;
            $twig_column_body[ $field_name ] = $property->compileTd() ;
        }
        
        // generate twig template
        $twig_writer
                ->writeln('{% block content_list_table %}' )
                ->writeln('<table class="table table-bordered">' )
                ->indent()
                ->writeln('<tr>' )
                ->indent()
        ;
        
        foreach($twig_column_header as $field_name => $header ) {
             $twig_writer
                    ->write('{% if admin.isPropertyVisiable("' . $field_name . '", action) %}')
                    ->write('{% ' . sprintf('block _admin_%s_label', $field_name ) .' %}' )
                    ->write( $header  )
                    ->write('{% endblock %}' )
                    ->writeln('{% endif %}')
             ;
        }
        
        foreach($_anonymous_children as $child_admin_name => $child_properties ) {
            $child_admin    = $this->admin_object->generator->getAdminByName( $child_admin_name ) ;
            
            if( !$child_admin->_final_template ) {
                continue ;
            }
            
            $twig_writer
                    ->writeln('{# if ' . sprintf('app_auth("%s", "list")', $child_admin->name ) .' #}')
                    ->indent()
                    ->writeln('{% ' . sprintf('import "%s" as child_macro', $child_admin->_final_template ) .' %}')
             ;
            foreach($child_properties as $child_property ) {
                $macro_name = 'admin_parent_' . $child_property ;
                $twig_writer
                        ->writeln('{% ' . sprintf('if twig_macro_exists(child_macro, "%s") ', $macro_name ). ' %}' )
                            ->writeln( '<th>')
                            ->indent()
                                ->writeln('{% ' . sprintf('if twig_macro_exists(child_macro, "%s_label") ', $macro_name ). ' %}' ) 
                                      ->indent()
                                      ->writeln( '{{ ' . sprintf('child_macro.%s_label(admin)', $macro_name ). ' }}' ) 
                                      ->outdent()
                                 ->writeln( '{% else %}') 
                                    ->indent()
                                    ->writeln( $child_admin->label->getTwigCode() ) 
                                    ->outdent()
                                ->writeln( '{% endif %}') 
                            ->outdent()
                            ->writeln( '</th>')
                        ->writeln( '{% endif %}') ;
            }
            $twig_writer
                    ->outdent()
                    ->writeln( '{# endif #}') ;
             ;
        }
        
        $twig_writer
               ->writeln('{% block admin_action_th %}<th>{% block admin_action_label %}#{% endblock %}</th>{% endblock %}' )
               ->outdent()
               ->writeln('<tr/>' )
               ->writeln('{% for _object in pagination %}' )
               ->indent()
               ->writeln('<tr>' )
        ;
        
        foreach($twig_column_body as $field_name => $body ) {
             $twig_writer
                    ->write('{% if admin.isPropertyVisiable("' . $field_name . '", action, _object ) %}')
                    ->write('{% set _property_value = ' . $this->children->properties[$field_name]->getTwigValue() .' %}')
                    ->write('{% ' . sprintf(' block _admin_%s_value ', $field_name ) .' %}' )
                    ->write($body )
                    ->write('{% endblock %}' )
                    ->writeln('{% endif %}')
             ;
        }
        
        foreach($_anonymous_children as $child_admin_name => $child_properties ) {
            $child_admin    = $this->admin_object->generator->getAdminByName( $child_admin_name ) ;
            if( !$child_admin->_final_template ) {
                continue ;
            }
            $twig_writer
                    ->writeln('{#% if ' . sprintf('app_auth("%s", "list")', $child_admin->name ) .' #}')
                    ->indent()
                    ->writeln('{% ' . sprintf('import "%s" as child_macro', $child_admin->_final_template ) .' %}')
             ;
            foreach($child_properties as $child_property ) {
                $macro_name = 'admin_parent_' . $child_property ;
                $twig_writer
                        ->writeln('{% ' . sprintf('if twig_macro_exists(child_macro, "%s") ', $macro_name ). ' %}' ) 
                            ->writeln( '<td>') 
                            ->indent()
                                ->writeln( '{{ ' . sprintf('child_macro.%s(app_admin_class(%s), admin, _object)', $macro_name, var_export($child_admin->class_name,1) ). '}}' ) 
                            ->outdent()
                            ->writeln( '</td>') 
                        ->writeln( '{% endif %}') ;
            }
            $twig_writer
                    ->outdent()
                    ->writeln( '{# endif #}') ;
             ;
        }
        
        // actions 
        $twig_writer
                ->writeln('{% block admin_action_td %}<td>{% block admin_action_value %}')
                ->indent()
                ->writeln('{% block admin_action_block %}')
                ->indent()
                ;
        
        foreach($this->admin_object->action_collection->children as $action) {
            if( $action->table ) {
                $twig_writer
                        ->write('{% if admin.auth("' . $action->name . '", _object) %}')
                        ->write('  <a href="{{ admin.path("' . $action->name . '", _object ) }}">')
                        ;
                if( $action->isWorkflowAuth() && $this->admin_object->workflow ) {
                    $twig_writer
                        ->write('{{ admin.getWorkflowUpdateLabel(_object, "' . $action->name . '") }}') ;
                } else {
                    $twig_writer
                        ->write('{{ admin.action("' . $action->name . '").label }}') ;
                }
                $twig_writer
                        ->write('</a>') 
                        ->writeln('{% endif %}')
                        ;
            }
        }
        $twig_writer
                    ->outdent() 
                    ->writeln('{% endblock admin_action_block %}') ;
        
        if( $this->admin_object->tree ) {
            $twig_writer
                        ->writeln('{% block admin_action_tree %}')
                        ->write('{% if admin.auth("list", _object) %}')
                        ->write('  <a href="{{ admin.path("list", _object ) }}">{{ "app.tree.child" | trans({ "%admin%": admin.label }, "' . $this->admin_object->app_domain . '") }}</a>')
                        ->write('{% endif %}')
                        ->writeln('{% endblock admin_action_tree %}' )
                        ;
        }
        
        if( $this->admin_object->workflow ) {
            $twig_writer
                    ->writeln('{% block admin_action_workflow %}')
                    ->indent()
                    ;
            foreach($this->admin_object->workflow->children as $node){
                if( $node->isInternal() ) continue ;
                if( $node->target && in_array('removed', $node->target) ) {
                    $twig_writer
                        ->write('{% if admin.checkWorkflowPermission(_object, "' . $node->name . '") %}')
                        ->write('  <a class="api_call" uri="{{ admin.getWorkflowActionPath(_object, "' . $node->name . '") }}">' . $node->action->getTwigCode() .'</a>')
                        ->writeln('{% endif %}')
                        ; 
                }
            }
            
            $twig_writer
                    ->outdent() 
                    ->writeln('{% endblock admin_action_workflow %}');
        }
        
        $twig_writer
                ->outdent()
                ->write('{% endblock admin_action_value %}' )
                ->write('</td>')
                ->writeln('{% endblock admin_action_td %}' )
                ;
        
        $twig_writer
               ->outdent()
               ->writeln('<tr/>' )
               ->writeln('{% endfor %}' )
               ->outdent()
               ->writeln('</table>' )
               ->writeln('{% endblock %}' )
        ;
        
        if( $this->admin_object->workflow ) {
            $twig_writer
                    ->writeln('{% block content_list_filter %}')
                    ->writeln( '{{ admin_macro.macro_workflow_report(admin) }}' )
                    ->writeln('{% endblock %}');
        }
        
        return $class ;
    }
}

