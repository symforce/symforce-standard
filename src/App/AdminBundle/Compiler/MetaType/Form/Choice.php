<?php

namespace App\AdminBundle\Compiler\MetaType\Form ;

use App\AdminBundle\Compiler\Annotation\FormType ;

/**
 * @FormType(orm="enum,string,integer,array")
 */
class Choice extends Element {
    
    public $enum ;
    
    /** @var bool */
    public $multiple ;
    
    /** @var bool */
    public $expanded ;

    /** @var mixed */
    public $empty_value ;
    
    /** @var mixed */
    public $empty_data ;
    
    /** @var mixed */
    public $preferred_choices ;
    
    /** @var mixed */
    public $choice_code ;
    
    /** @var mixed */
    public $choices ;
    
    /** @var bool */
    public $by_reference ;
    
    /** @var bool */
    public $virtual ;

    static public function getSupportedDoctrineType(){
        return array( 'enum', 'string', 'integer' ) ;
    }
    
    public function set_enum( $enum ){
        $this->enum = $enum ;
    }
    
    public function set_choices( $value ) {
        // @todo convert it to array
        if( is_array($value) ) {
            $this->choices     = array() ;
            $is_simple_array = true ;
            
            $keys   = array_keys( $value ) ;
            foreach( $keys as $i => $_i ) {
                if( $i !== $_i  ) {
                    $is_simple_array    = false ;
                    break ;
                }
            }
            if( $is_simple_array ) {
               foreach( $value as $k => $v ) {
                    if( is_object($v) ) {
                        $option  = new Option( $v , null, null ) ;
                    } else { 
                        $option  = new Option( $v , null, null ) ;
                    }
                    if( isset($this->choices[ $option->key  ]) ) {
                        throw new \Exception(sprintf("duplicate key(%s)", $option->key)) ;
                    }
                    $this->choices[ $option->key ] = $option ;
               }
            } else {
                foreach( $value as $k => $v ) {
                    if( is_object($v) ) {
                        if( !isset($v['value']) ) {
                            $v['value'] = $k ;
                        }
                        $option  = new Option( $v , null, null ) ;
                    } else { 
                        $option = new Option( $k, $v, null ) ;
                    } 
                    if( isset($this->choices[ $option->key  ]) ) {
                        throw new \Exception(sprintf("duplicate key(%s)", $option->key)) ;
                    }
                    $this->choices[ $option->key  ] = $option ;
               }
            }
            
        } else {
            // named choice options 
             echo "\n",__FILE__, "\n", __LINE__, "\n"; exit ;
        } 
    }
    
    public function getChoices(){
        return $this->choices ;
    }
    
    protected function fixOption(Option $option){
        if( !$option->getLabel() ) {
            $option->setLabel( $this->property_container->tr_node ,  $this->class_property . '.choice' );
        }
    }
    
    public function getFormOptions() {
        
        $admin  = $this->admin_object->getCompileClass();
        
        $choices    = $this->getChoices() ;
        
        if( $choices ) {
            $tr_path_map    = array() ;
            foreach($choices  as $option ) {
                $this->fixOption( $option ) ;
                $tr_path_map[ $option->value ] = array($option->getLabel()->getPath(), $option->getLabel()->getDomain() !== $this->admin_object->tr_domain ) ;
            }
            $admin->addLazyArray( 'form_choices', $this->class_property , $tr_path_map ) ;
        }
        
        $options    = parent::getFormOptions() ;
        
        
        if( null !== $this->choice_code ) {
            if( $this->choices ) {
                $this->throwError(' can not use choices_code with choices') ;
            } 
            if( false == strpos($this->choice_code, '$') ) {
                $options['choices']    = $this->compilePhpCode(sprintf('$this->%s($object)', $this->choice_code) ) ;
            } else {
                $options['choices']    = $this->compilePhpCode($this->choice_code ) ;
            }
        } else {
            $choices    = $this->getChoices() ;
            if( null !== $choices ) {
                $choice_options   = array() ;
                foreach($choices as $option){
                    if( !is_object($option) ) {
                        \Dev::dump($option) ; exit ;
                    }
                    $this->fixOption( $option ) ;
                    $choice_options[ $option->value ] = $option->getLabel()->getPhpCode() ;
                }
                $options['choices'] = $choice_options ;
            }
        }
        
        if( $this->expanded ) {
            $options['expanded']  = true ;
        }
        
        if( $this->multiple ) {
            $options['multiple']  = true ;
        }
        
        if( $this->virtual ) { 
            $options['virtual']  = true ;
        }
        
        if( null === $this->empty_value ) {
            if( $this->isNullable() ) {
                 $this->empty_value  = true ;
            }
        }
        
        if( $this->empty_value ) {
            $options['empty_value']    = $this->empty_value ;
            $options['empty_data']    = $this->empty_data ;
            $this->required = false ;
            $options['required']    = false ;
        }
        
        return $options ;
    }
    
}
