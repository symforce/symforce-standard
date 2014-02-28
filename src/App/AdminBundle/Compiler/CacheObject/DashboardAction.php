<?php

namespace App\AdminBundle\Compiler\CacheObject ;

/**
   * @todo add route path name
   */

class DashboardAction {
    
    protected $name ;

    /**
     * @var string
     */
    protected $label ;
    
    /**
     * @var string
     */
    protected $domain ;
  
    public function __construct( $name ) {
        $this->name   = $name ;
    }
    
    public function getName(){
       return $this->name ;
    }
    
    public function setLabel($value) {
        $this->label  = $value ;
    }
    
    public function getLabel() {
        return $this->label;
    }
    
    public function setDomain($value) {
        $this->domain  = $value ;
    }
    
    public function getDomain() {
        return $this->domain ;
    }
}
