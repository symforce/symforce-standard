<?php

namespace App\AdminBundle\Compiler\MetaType\Form ;

use App\AdminBundle\Compiler\MetaType\Type ;

class Option extends Type {
    
    public $key ;
    
    /**
     * @var string 
     */
    public $text ;
    
    public $value ;
    
    /**
     * @var \App\AdminBundle\Compiler\Generator\TransGeneratorValue
     */
    protected $label ;
    
    public function __construct( $value , $text , $key ) {
        $this->text = $text ;
        
        if( is_object($value) || is_array($value) ) {
            $this->setMyPropertie( $value ) ;
        } else {
            $this->value = $value ;
        }
        
        if( $key  ) {
            if( null === $this->key ) {
                if( preg_match('/^\d+/', $key ) ) {
                     $this->key = 'option_' . $key ;
                } else if( preg_match('/^[\w\_]+/', $key ) ) {
                     $this->key = strtolower($key) ;
                } else {
                    throw new \Exception(sprintf("invalid key(%s) for option", $key ));
                }
            } else {
                 throw new \Exception(sprintf("duplicate key(%s,%s)", $key, $this->key ));
            }
        }  else {
            if( null === $this->key && null !== $this->text ) {
                if( preg_match('/^\d+$/', $this->text ) ) { 
                    $this->key = 'option_' . $this->text ;
                } else if( preg_match('/^[\w\_]+$/', $this->text ) ) { 
                    $this->key = strtolower($this->text) ;
                }
            }
            
            if( null === $this->key && null !== $this->value ) {
                if( preg_match('/^\d+$/', $this->value ) ) { 
                    $this->key = 'option_' . $this->value ;
                } else if( preg_match('/^[\w\_]+$/', $this->value) ) {
                    $this->key = strtolower($this->value) ;
                }
            }

            if( null === $this->key ) {
                throw new \Exception(sprintf("no key for option"));
            }
        }
        
        if( !$this->text ) {
            $this->text = $this->value ;
        }
    }
    
    public function set_text( $value ) {
        if( is_array($value) || is_object($value) ) {
            $this->throwPropertyError('text', " can not be `%s`", json_encode($value) ) ;
        }
        $this->text = $value ;
    }
    
    public function setLabel( \App\AdminBundle\Compiler\Generator\TransGeneratorNode $node, $path, $domain = null ) {
        $this->label    = $node->createValue( $path . '.' . $this->key , $this->text, $domain ) ;
    }
    
    /**
     * @return \App\AdminBundle\Compiler\Generator\TransGeneratorValue
     */
    public function getLabel() {
        return $this->label ;
    }
}