<?php

namespace App\AdminBundle\Compiler\MetaType\Action ;

class ActionPropertyGroup extends \App\AdminBundle\Compiler\MetaType\Type {
    
    /**
     * @var \App\AdminBundle\Compiler\Generator\TransGeneratorValue 
     */
    public $label ;
    
    public $name ;
    public $id ;
    
    public $properties = array() ;
    public $position_properties ;

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
    
    public function set_label( $label ) {
        if( $label instanceof \App\AdminBundle\Compiler\Generator\TransGeneratorValue ) {
            $this->label    = $label ;
        } else {
            $this->throwError("can not set label `%s`, you should use name", $label ) ;
        }
    }
    
    public function fixLabel( \App\AdminBundle\Compiler\Generator\TransGeneratorNode $tr, $app_domain ){
        if( !$this->label ) {
            $path   = 'group.' . strtolower( $this->id ) ;
            if( $this->name ) {
                $this->label    = $tr->createValue( $path , $this->name );
            } else {
                $this->label    = $tr->createValue( 'app.form.' . $path , null, $app_domain );
            }
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
    
}