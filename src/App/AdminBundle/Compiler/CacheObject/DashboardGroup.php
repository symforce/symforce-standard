<?php

namespace App\AdminBundle\Compiler\CacheObject ;

class DashboardGroup {

    /**
     * @var string
     */
    protected $name ;
    
    /**
     * @var string
     */
    protected $label ;
    
    /**
     * @var string
     */
    protected $postion ;
    
    /**
     * @var bool
     */
    protected $right_side ;
    
    protected $icon ;

    public $children = array() ;
    
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
    
    public function setPosition($value) {
        $this->postion  = (int) $value ;
    }
    
    public function setRightSide($value) {
        $this->right_side  = $value ;
    }
    
    public function getRightSide() {
        return $this->right_side;
    }
    
    public function getIcon() {
        return $this->icon ;
    }
    
    public function setIcon( $icon ) {
         $this->icon = $icon ;
    }
    
    public function addChild(DashboardItem $child){
        
        $child->setGroup($this) ;
        
        $name   = $child->getName() ;
        if( !isset($this->children[$name]) ) {
            $this->children[$name]  = $child ;
        } else {
            if( $this->children[$name] !== $child ) {
                throw new \Exception("duplicate child");
            }
        }
    }
    
    public function getChildren(){
        return $this->children ;
    }
    
    public function hasChildren() {
        return count($this->children) > 0 ;
    }
}