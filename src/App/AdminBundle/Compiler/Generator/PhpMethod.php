<?php

namespace App\AdminBundle\Compiler\Generator;

class PhpMethod extends \CG\Generator\PhpMethod {
    
    /**
     * @var \App\AdminBundle\Compiler\Generator\PhpWriter
     */
    protected $_writer ;
    
    protected $_lazy_code ;
    
    protected $_lazy_parent ;


    /**
     * @return \App\AdminBundle\Compiler\Generator\PhpWriter
     */
    public function getWriter(){
        if( null === $this->_writer ) {
            $this->_writer  = new \App\AdminBundle\Compiler\Generator\PhpWriter();
        }
        return $this->_writer ;
    }
    
    /**
     * @param bool $value
     * @return \App\AdminBundle\Compiler\Generator\PhpMethod
     */
    public function useLazyParent( $value = true ) {
        $this->_lazy_parent = !! $value ;
        return $this ;
    }
    
    
    /**
     * @param string $code
     * @return \App\AdminBundle\Compiler\Generator\PhpMethod
     */
    public function addLazyCode($code){
        if( null == $this->_lazy_code ) {
            $this->_lazy_code   = array() ;
        }
        $this->_lazy_code[] = (string) $code ;
        return $this ;
    }
    
    
    public function flushLazyCode(){
        if( $this->_lazy_code ) {
            foreach($this->_lazy_code as $ln) {
                $this->_writer->writeln( $ln ) ;
            }
            $this->_lazy_code   = null ;
        }
        if( $this->_lazy_parent) {
            $this->writeCallParent() ;
        }
    }
    
    public function writeCallParent() {
        $ps = $this->getParameters() ;
        $_ps    = array() ;
        if($ps) foreach($ps as $p) {
            $_ps[]  = '$' . $p->getName() ;
        }
        $code   = 'parent::' . $this->getName() . '(' . join(', ', $_ps). ');' ;
        $this->_writer->writeln($code) ;
    }
}