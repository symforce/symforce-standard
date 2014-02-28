<?php

namespace App\AdminBundle\Compiler\Generator;

class PhpProperty extends \CG\Generator\PhpProperty {
    
    /**
     * @var PhpClass 
     */
    protected   $_class = null ;
    
    /**
     * @var string 
     */
    private     $_name = null ;
    
    /**
     * @var bool 
     */
    protected   $_get = null ;
    
    /**
     * @var string 
     */
    protected   $_type = null ;
    
    /**
     * @var bool 
     */
    protected   $_lazy = null ;

    public function __construct($name = null) {
        $this->setName($name);
    }
    
    /**
     * @param \App\AdminBundle\Compiler\Generator\PhpClass $class
     * @return \App\AdminBundle\Compiler\Generator\PhpProperty
     */
    public function setClass(PhpClass $class){
        $this->_class   = $class ;
        $class->setProperty( $this ) ;
        return $this ;
    }
    
    /**
     * @param bool $_get
     * @return \App\AdminBundle\Compiler\Generator\PhpProperty
     */
    public function useGetter( $_get ){
        $this->_get = !! $_get ;
        return $this ;
    }
    
    /**
     * @param bool $_lazy
     * @return \App\AdminBundle\Compiler\Generator\PhpProperty
     */
    public function setLazy( $_lazy = true ){
        $this->_lazy = !! $_lazy ;
        return $this ;
    }
    
    /**
     * @param string $type
     * @return \App\AdminBundle\Compiler\Generator\PhpProperty
     */
    public function setType( $type ){
        $this->type = $type ;
        return $this;
    }
    
    public function getFixedName() {
        if( null === $this->_name ) {
            $this->_name = preg_replace_callback('/(^|_|\.)+(.)/', function ($match) {
                        return ('.' === $match[1] ? '_' : '') . strtoupper($match[2]);
                    }, $this->getName() ) ; 
        }
        return $this->_name ;
    }
    
    public function genGetter(){
        if( $this->_get  ) {
            
            $get_name = 'get' . ucfirst( $this->getFixedName() ) ;
            
            $this->_class->setMethod(
                        \CG\Generator\PhpMethod::create( $get_name )
                        ->setFinal(true)
                        ->setDocblock('/** @return ' .  $this->type  . ' */')
                        ->setBody('return $this->' . $this->getName() . ';')
            ) ;
        }
        return $this ;
    }
    
    public function writeCache(\App\AdminBundle\Compiler\Generator\PhpWriter $lazy_writer , \App\AdminBundle\Compiler\Generator\PhpWriter $writer ) {
        
        $default_value  = $this->getDefaultValue() ;
        if( $this->_lazy ) {
            $lazy_writer->writeln( '$this->' . $this->getName() . ' = ' .  $this->_class->propertyEncode( $default_value )  . ' ; ' );
            $default_value  = null ;
        }

        $writer->write("\n") ;
        if( $this->getDocblock() ) {
            $writer->writeln( $this->getDocblock() ) ;
        }
        $writer
                ->write( $this->getVisibility() ) 
                ->write( ' $' ) 
                ->write( $this->getName() ) 

                ->write( ' = ' )
                ->write(  $this->_class->propertyEncode( $default_value ) )
                ->writeln(" ;")
                ;

        if( $this->_get ) {
            $this->genGetter() ;
        }
    }
}