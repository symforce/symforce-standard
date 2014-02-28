<?php

namespace App\AdminBundle\Compiler\MetaType;

abstract class Type {
    
    private $freeze = false ;
    protected $disabled ;
    
    /**
     * @var \ReflectionObject
     */
    private $_meta_self_reflection ;
    
    protected $_annot_properties    = array() ;


    public function setMyPropertie( $object = null ) {
        if( null === $this->_meta_self_reflection ) {
            $this->_meta_self_reflection  = new \ReflectionObject( $this ) ;
        }
        $this->freeze   = false ;
        if( is_array($object) || is_object($object) ) {
            // $access = \\Symfony\Component\PropertyAccess\PropertyAccess::getPropertyAccessor() ;
            foreach($object as $property => $value ) {
                if( null === $value ) {
                    continue ;
                }
                $set    = 'set_' . $property ; 
                try {
                    if( method_exists($this, $set) ) {
                        if( $this->$set( $value ) ) {
                            $this->throwError("%s already seted", $property);
                        }
                    } else {
                        if( !$this->_meta_self_reflection->hasProperty($property) ) {
                            $this->throwError("%s not exists", $property);
                        }
                        $_set    = 'set' . $this->camelize($property) ; 
                        if( method_exists($this, $_set) ) {
                            throw new \Exception(sprintf("%s->%s method %s should change to %s", $this->getMeteTypeName(), $property, $_set, $set ));
                        }
                        if( 0 === strpos($property, '_') ) {
                            $this->throwError("%s is private property", $property);
                        }
                        $_property = $this->_meta_self_reflection->getProperty( $property ) ;
                        if( $_property->isStatic() ) {
                            $this->throwError("%s is static property", $property);
                        }
                        if( $_property->isPrivate() ) {
                            $this->throwError("%s is private property", $property);
                        }
                        /*
                        $_property->setAccessible( true ) ;
                        $_property->setValue( $this, $value ) ;
                         */
                        $this->$property    = $value ;
                    }
                    
                    $this->_annot_properties[ $property ] = true ;
                    
                } catch( \App\AdminBundle\Compiler\MetaType\Exception $e ) {
                    if( 
                            $this instanceof \App\AdminBundle\Compiler\MetaType\DoctrineType || 
                            $this instanceof \App\AdminBundle\Compiler\MetaType\DoctrineProperty
                    ) {
                        $this->throwPropertyError(  $property, $e->getMessage() ) ;
                    } else {
                        throw  $e ;
                    }
                }
            }
        } else if( ! empty( $object) ){
            throw new \Exception(sprintf("%s can not setValue(%s)", $this->getClassName(), $value ) );
        }
        $this->freeze   = true ;
    }
    
    public function isFreeze(){
        return $this->freeze ;
    }
    
    public function unFreeze(){
        $this->freeze   = false ;
    }
    
    /**
     * Error handler for unknown property accessor in Annotation class.
     *
     * @param string $name Unknown property name
     *
     * @throws \BadMethodCallException
     */
    public function __get($name)
    {
        $this->throwPropertyError($name, "not exists");
    }

    /**
     * Error handler for unknown property mutator in Annotation class.
     *
     * @param string $name Unkown property name
     * @param mixed $value Property value
     *
     * @throws \BadMethodCallException
     */
    public function __set($name, $value)
    {
        $this->throwPropertyError($name, "not exists");
    }
    
    public function getMeteTypeName() {
        return str_replace( __NAMESPACE__ , 'Admin',  get_class($this) );
    }
    
    protected $_throw_append_message ;
    
    public function throwError() {
        $args   = func_get_args() ;
        $argv   = count($args) ;
        if( !$argv ) {
            $msg    = '' ;
        } else if( $argv == 1 ) {
            $msg    = $args[0] ;
        } else {
            $msg    = call_user_func_array('sprintf', $args ) ;
        }
        
        $_msg   = $this->_throw_append_message ;
        $this->_throw_append_message = null ;
        
        if( $_msg ) {
            throw new Exception( sprintf("%s for @%s%s",  $msg, $this->getMeteTypeName(), $_msg ) ) ;
        } else {
            throw new Exception( sprintf("%s for @%s",  $msg, $this->getMeteTypeName() ) ) ;
        }
    }
    
    public function throwPropertyError() {
        $args   = func_get_args() ;
        $property   = array_shift( $args ) ;
        $this->_throw_append_message = sprintf("->%s", $property ) ;
        $argv   = count($args) ;
        if( !$argv ) {
            $msg    = '' ;
        } else if( $argv == 1 ) {
            $msg    = $args[0] ;
        } else {
            $msg    = call_user_func_array('sprintf', $args ) ;
        }
        $this->throwError( $msg ) ;
    }
    
    public function compilePhpCode( $code ){
        return '#php{% ' . $code . ' %}' ; 
    }
    
    public function camelize($string)
    {
        return preg_replace_callback('/(^|_|\.)+(.)/', function ($match) { return ('.' === $match[1] ? '_' : '').strtoupper($match[2]); }, $string);
    }
    
}