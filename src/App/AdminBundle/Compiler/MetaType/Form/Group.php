<?php

namespace App\AdminBundle\Compiler\MetaType\Form ;

class Group extends \App\AdminBundle\Compiler\MetaType\Type {
    
    /**
     * @var \App\AdminBundle\Compiler\Generator\TransGeneratorValue 
     */
    public $label ;
    
    public $name ;
    public $id ;
    public $position ;
    
    public $properties = array() ;
    public $position_properties ;
    
    public $show_on ;

    public function __construct( $annot ) {
        if( is_scalar($annot) ) {
            $this->set_id( $annot ) ;
        }  else {
            $this->setMyPropertie($annot);
        }
    }
    
    public function set_id( $id ) {
        if( preg_match('/\W/', $id ) ) {
            $this->throwPropertyError('id', "invalid value `%s`", $id ) ;
        }
        $this->id = $id ;
    }
    
    public function set_name($name) {
        $this->name = $name ;
    }
    
    public function fixLabel( \App\AdminBundle\Compiler\Generator\TransGeneratorNode $tr, $app_domain ){
        $path   = 'group.' . strtolower( $this->id ) ;
        if( $this->name ) {
            $this->label    = $tr->createValue( $path , $this->name ) ;
        } else {
            $this->label    = $tr->createValue( 'app.form.' . $path , null, $app_domain );
        }
        
    }
    
    public function add($property, $position) {
        if( null === $position ) {
            $this->properties[] = $property ;
        } else {
            if( !$this->position_properties ) {
                $this->position_properties  = array() ;
            }
            $this->position_properties[ $property ] = $position ;
        }
    }
    
    public function sort() {
        if( ! $this->position_properties ) {
            return ;
        }
        // sort $position_properties 
        asort( $this->position_properties ) ;
        
        foreach($this->position_properties as $property => $position ) {
            $splice_finished   = false ;
            foreach($this->properties as $_position => $_property ) {
                if( $_position >= $position ) {
                    array_splice($this->properties, $_position, 0, $property );
                    $splice_finished   = true ;
                    break ;
                }
            }
            if( !$splice_finished ) {
                $this->properties[]  = $property ;
            }
        }
        $this->position_properties  = null ;
    }
    
    public function compileForm($action, $parent_builder, $admin , $object, \App\AdminBundle\Compiler\MetaType\PropertyContainer $property_container, \App\AdminBundle\Compiler\Generator\PhpWriter $writer, $parent_property = null){
            $this_builder   = $parent_builder . '_' . $this->id ;
            $options    = array(
                "appform_type"  => "appgroup" ,
                "label" =>  $this->label->getPhpCode() ,
            );
            
            if( $this->show_on ) {
                if( !is_array($this->show_on) ) {
                    $this->throwError("show_on need be array, you set %s", gettype($this->show_on) );
                }
                $_or   = $this->show_on ;
                if( !\Dev::isSimpleArray($this->show_on) ) {
                    $_or   = array( $_or ) ;
                }
                $options['dynamic_show_on'] = $_or ;
            }
            
            $writer->write(  $this_builder  . ' = $builder->create("app_form_group_' . $this->id . '", "appgroup", ')
                    ->write(var_export($options, 1) ) 
                    ->writeln(');');

            foreach($this->properties as $property_name ) {
                if( !$property_container->hasProperty($property_name) ) {
                    continue ;
                }
                $element    = $property_container->properties[ $property_name ] ;
                $element->compileActionForm( $action , $this_builder , $admin , $object, $parent_property ) ;
            }
            
            $writer
                    ->writeln( sprintf('if( %s->count() > 0 ) { ', $this_builder ) )
                    ->indent()
                    ->writeln( $parent_builder . '->add(' . $this_builder . ');')
                    ->outdent()
                    ->writeln("}")
            ;
    }
}