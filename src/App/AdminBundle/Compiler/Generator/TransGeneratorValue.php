<?php

namespace App\AdminBundle\Compiler\Generator ;

/**
 * Description of TransGeneratorNode
 *
 * @author loong
 */
class TransGeneratorValue {
    
    /**
     * @var string 
     */
    protected $domain ;

    /**
     * @var array 
     */
    protected $path ;
    
    /**
     * @var array 
     */
    protected $value ;
    
    /**
     * @var boolean 
     */
    protected $changed ;
    
    public function __construct($path, $domain, $value ) {
        $this->path = $path ;
        $this->domain   = $domain ;
        $this->value = $value ;
    }
    
    public function getPath() {
        return $this->path ;
    }
    
    public function getDomain() {
        return $this->domain ;
    }
    
    public function getValue() {
        return $this->value ;
    }
    
    public function setValue( $value ) {
         $this->value = $value ;
         $this->changed = true ;
    }
    
    public function isNull() {
        return null === $this->value ;
    }
    
    public function isChanged() {
        return $this->changed ;
    }
    
    
    public function getArray() {
        return array( $this->path, $this->domain ) ;
    }
    
    public function getTwigCode() {
        return  '{{ "' . $this->path . '" |trans({}, "' . $this->domain . '") }}' ;
    }
    
    public function getPhpCode() {
        return self::compilePhpCode( $this->getNakePhpCode() ) ;
    }
    
    public function getNakeTwigCode() {
        return '"' . $this->path . '" |trans({}, "' . $this->domain . '")' ;
    }
    
    public function getNakePhpCode() {
        return '$this->trans("' .  $this->path. '", null, "' .  $this->domain . '")' ;
    }
    
    static public function compilePhpCode( $code ){
        return '#php{% ' . $code . ' %}' ; 
    }
}
