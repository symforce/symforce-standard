<?php

namespace App\AdminBundle\Compiler\MetaType\Form ;

use App\AdminBundle\Compiler\Annotation\FormType ;

/**
 * @FormType("appembed", map="*")
 */
class Embed extends Element {
    
    /**
     * @var \App\AdminBundle\Compiler\MetaType\PropertyContainer 
     */
    public $children ;
    
    public $copy_properties ;
    
    public function lazyInitialize() {
        parent::lazyInitialize() ;
    }
    
    protected function set_copy_properties( $value ){
        if( !is_array($value) ) {
            $this->throwError("copy_properties value must be array");
        }
        $this->copy_properties  = $value ;
    }
    
    public function getFormOptions() {
        /**
         * @todo add code to detect infinite loop
         */
        $map    = $this->admin_object->orm_metadata->getAssociationMapping( $this->class_property ) ;
        $parent_class   = $map['targetEntity'] ;
        
        foreach($this->copy_properties as $from_property => $to_property ) {
            if( !is_string($from_property) ) {
                $this->throwError("copy_properties source property must be string, you set:%s!", gettype($from_property) );
            }
            if( !property_exists( $this->admin_object->class_name, $from_property ) ) {
                $this->throwError("copy_properties source property:%s not exists!", $from_property );
            }
            if( !is_string($to_property) ) {
                $this->throwError("copy_properties target:%s property must be string, you set:%s!", $parent_class, gettype($to_property) );
            }
            if( !property_exists( $parent_class , $to_property) ) {
                $this->throwError("copy_properties target:%s->%s not exists!", $parent_class, $to_property );
            }
        }
        
        $options    = parent::getFormOptions() ;
        return $options ;
    }
    
    public function compileActionForm(\App\AdminBundle\Compiler\MetaType\Action\AbstractAction $action, $builder = '$builder', $admin = '$this' , $object = '$object', $parent_property = null ){
        
        $is_page_property   = false ;
        $copy_properties    = $this->copy_properties ;
        if( $this->admin_object->page  && $this->class_property  === $this->admin_object->page->page_property ) {
            $is_page_property   = true ;
            $_copy_properties = $this->admin_object->page->getCopyProperties() ;
            if( $copy_properties ) {
                $copy_properties    = array_merge( $copy_properties, $_copy_properties ) ;
            } else {
                $copy_properties    = $_copy_properties ;
            }
        }
        
        $map    = $this->admin_object->orm_metadata->getAssociationMapping( $this->class_property ) ;
        $parent_class   = $map['targetEntity'] ;
        $parent_admin   = $this->admin_object->generator->getAdminByClass( $parent_class ) ;
        
        $writer = $action->getCompileFormWriter() ;
        
        $prefix = $admin . '_' . $this->class_property ;
        $_embed_admin =  $prefix . '_admin' ;
        $_embed_property  = $prefix . '_property' ;
        $_embed_object = $prefix . '_object' ;
        $_embed_builder = $prefix . '_builder' ;
        
        $writer->writeln( $_embed_admin . '   = $this->admin_loader->getAdminByClass("' . $parent_class . '");');
        
        if( $is_page_property ) {
            $writer->writeln( $_embed_object . ' = ' . $admin .'->getPageObject(' . $object . ' ) ;') ;
            $writer->writeln( sprintf("%s->fixPageEntityId(%s, %s);", $admin, $object, $_embed_object )) ;
            
        } else {
            $writer->writeln( $_embed_property . ' = '. $admin .'->getReflectionProperty("' . $this->class_property . '") ;');
            $writer->writeln( $_embed_object . ' = ' . $_embed_property .'->getValue(' . $object . ' ) ;');
            $writer
                    ->writeln('if( null === ' .  $_embed_object . ' ) {')
                    ->indent()
                    ->writeln( $_embed_object . ' = ' . $_embed_admin . '->newObject();')
                    ->writeln( $_embed_property . '->setValue(' . $object . ',  ' . $_embed_object . ' );')
                    ->outdent()
                    ->writeln('}')
                    ;
        }
        
        $writer->writeln('if('. $admin . '->isPropertyVisiable("' . $this->class_property . '", $action, ' . $object . ')) {')->indent() ;

        $embed_options    = array(
                    // "label" =>  $parent_admin->label->getPhpCode() ,
                    "label_render" =>  false ,
                    "copy_properties" => $copy_properties ,
                    "target_entity"    => $parent_class ,
                    "source_entity"    => $this->admin_object->class_name ,
            
                    "horizontal_input_wrapper_class"    => "" ,
                    "horizontal_label_class"    => "" ,
                    "horizontal_label_offset_class" => "" ,
                );
        
        $writer->write( $_embed_builder . ' = $controller->get("form.factory")->createNamedBuilder("' .$this->class_property  . 
                 '", "' .  $this->compile_form_type . '", ' . $_embed_object . ',')
                 ->write( var_export($embed_options, 1) )
                 ->writeln(');');
        
        /**
         * @todo add group for it ?
         */
        if( 1 === count($parent_admin->form->groups) ) {
            foreach($this->children->properties as $element ) {
                $element->compileActionForm($action, $_embed_builder , $_embed_admin , $_embed_object, $this->class_property ) ;
            }
        } else {
            foreach($parent_admin->form->groups as $group) {
                $group->compileForm($action, $_embed_builder, $_embed_admin, $_embed_object, $this->children, $writer , $this->class_property ) ;
            }
        }
        
        $writer->writeln( $builder . '->add(' . $_embed_builder . ', "' . $this->class_property . '");');
        
        $writer->outdent()->writeln('} else {')->indent() ; 
        $hidden_options    = array(
            "children_render" =>  false ,
            "compound"  => false ,
            "copy_properties" => $copy_properties ,
            "target_entity"    => $parent_class ,
            "source_entity"    => $this->admin_object->class_name ,
        );
        
        $writer->write( $builder . '->add("' .$this->class_property  . '", "' .  $this->compile_form_type . '", ' )
                 ->write(var_export($hidden_options, 1))
                 ->writeln(');');
        $writer->outdent()->writeln('} ') ; 
        
        $admin_class    = $this->admin_object->getCompileClass() ;
        $admin_class->addLazyArray( 'copy_properties',  $this->class_property, $copy_properties ) ;
        /*
        $this->admin_object->generator->setDoctrineConfig( $this->admin_object->class_name, 'copy', $this->class_property, $copy_properties );
        */
    }
}
