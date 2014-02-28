<?php

namespace App\AdminBundle\Compiler\CacheObject ;

class DashboardItem {
    
    /**
     * @var DashboardGroup
     */
    protected $group ;
    
    /**
     * @var string
     */
    public $name ;
    
    /**
     * @var string
     */
    protected $domain ;
    
    /**
     * @var string
     */
    protected $label ;
    
    /**
     * @var string
     */
    public $postion ;
    
    protected $icon ;
    
    /**
     * @var string
     */
    protected $actions = array() ;
    
    public function __construct( $name ) {
        $this->name   = $name ;
    }
    
    public function getName(){
       return $this->name ;
    }
    
    public function setPosition($value) {
        $this->postion  = (int) $value ;
    }
    
    public function setDomain($value) {
        $this->domain  = $value ;
    }
    
    public function getDomain() {
        return $this->domain ;
    }
    
    public function setLabel($value) {
        $this->label  = $value ;
    }
    
    public function getLabel() {
        return $this->label;
    }
    
    public function setEntity($value) {
        $this->entity   = $value ;
    }
    
    public function getEntity() {
        return $this->entity  ;
    }
    
    public function getGroup() {
        return $this->group  ;
    }
    
    public function setGroup($group) {
         $this->group = $group ;
    }
    
    public function getIcon() {
        return $this->icon ;
    }
    
    public function setIcon( $icon ) {
         $this->icon = $icon ;
    }
    
    public function getActions() {
        return $this->actions ;
    }
    
    public function addAction(DashboardAction $action ) {
        $this->actions[] = $action ;
    }
    
}

