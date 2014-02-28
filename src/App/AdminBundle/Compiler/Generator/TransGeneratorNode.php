<?php

namespace App\AdminBundle\Compiler\Generator ;

use App\AdminBundle\Compiler\Generator ;

/**
 * Description of TransGeneratorNode
 *
 * @author loong
 */
class TransGeneratorNode {
    
    /**
     * @var array 
     */
    protected $data = array() ;
    
    /**
     * @var array 
     */
    protected $values = array() ;
    
    /**
     * @var array 
     */
    protected $path ;
    
    /**
     * @var string 
     */
    protected $domain ;
    
    /**
     * @var Generator
     */
    protected $gen ;

    public $debug ;

    public function getPath() {
        return $this->path ;
    }
    
    public function getDomain() {
        return $this->domain ;
    }

    private function & getPathArray( $path ) {
        $_path  = explode('.',  $path ) ;
        // $_path  = array_filter( $_path , 'trim' ) ;
        return $_path ;
    }

    private function & findArray( array & $_path ) {
        $_data  =& $this->data  ;
        while( count($_path) ) {
            $_key   = array_shift( $_path ) ;
            if( !isset( $_data[$_key] ) ) {
                $_data[$_key]   = array() ;
            }
            $_data  =& $_data[$_key] ;
        }
        return $_data ;
    }
    
    /**
     * @return TransGeneratorNode
     */
    public function set( $path, $value ) {
        if( false === strpos($path, '.') ) {
            $this->data[ $path ] = $value ;
        } else {
            $_path  = $this->getPathArray( $path ) ;
            $this->put($_path, $value ) ;
        }
        return $this ;
    }
    
    /**
     * @return TransGeneratorNode
     */
    public function put( array $_path, $value ) {
        $__key  = array_pop( $_path ) ;
        $_data  =& $this->findArray( $_path ) ;
        $_data[ $__key ] = $value ;
        return $this ;
    }
    
    /**
     * @return TransGeneratorNode
     */
    private function createNodeByRef( array & $data, $_path  ) {
        if( ! $this->domain ){
            throw new \Exception( 'domain can not be null' );
        }
        $node   = new TransGeneratorNode() ;
        $node->data =& $data ;
        if( $this->path ) {
            $node->path = $this->path . '.' . $_path ;
        } else {
            $node->path = $_path ;
        }
        $node->domain   = $this->domain ;
        $node->gen = $this->gen ;
        return $node ;
    }
    
    /**
     * @param type $path
     * @return TransGeneratorNode
     */
    public function getNodeByPath( $path ) {
        if( false === strpos($path, '.') ) {
            if( !isset($this->data[ $path ]) ) {
                $this->data[ $path ]    = array() ;
            }
            $node   = $this->createNodeByRef( $this->data[ $path ] , $path ) ;
        } else {
            $_path  = $this->getPathArray( $path ) ;
            $node   = $this->getNodeByRef( $_path ) ;
        }
        return $node ;
    }
    
    /**
     * @return TransGeneratorNode
     */
    public function getNodeByRef( array & $path ) {
        $_path  = join('.', $path ) ;
        $_data  =& $this->findArray( $path ) ;
        return $this->createNodeByRef( $_data, $_path ) ;
    }
    
    /**
     * @return TransGeneratorNode
     */
    public function getNode( array $path ) {
        return $this->getNodeByRef( $path ) ;
    }
    
     /**
     * @return TransGeneratorValue
     */
    public function createValue($path , $string , $ref_domain = null ) {
        if( null === $ref_domain ) {
            $this->set( $path, $string ) ;
            if( $this->path ) {
                $path   =  $this->path . '.' . $path ;
            }
            return new TransGeneratorValue( $path ,  $this->domain, $string ) ;
        } else {
            $tr     = $this->gen->getTransGenerator( $ref_domain ) ;
            $tr->set($path, $string) ;
            return new TransGeneratorValue( $path ,  $ref_domain , $string ) ;
        }
    }
}
