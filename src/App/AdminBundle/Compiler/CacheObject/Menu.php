<?php

namespace App\AdminBundle\Compiler\CacheObject ;

/**
 * @todo add route path name
 */
class Menu
{ 
    /**
     * @var Menu
     */
    protected $parent ;
    
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
    protected $domain ;
    
    /**
     * @var string
     */
    protected $postion ;
    
    /**
     * @var string
     */
    protected $route_name ;
    
    /**
     * @var string
     */
    protected $url ;
    
    /**
     * @var bool
     */
    public $admin ;
    
    protected $divider ;

    protected $icon ;

    protected $children = array() ;
    
    public function __construct( $name ) {
        $this->name   = $name ;
    }
    
    public function getName(){
       return $this->name ;
    }
    
    
    public function setPosition($value) {
        $this->postion  = (int) $value ;
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
    
    public function getParent() {
        return $this->parent  ;
    }
    
    public function setRouteName ($value) {
        $this->route_name = $value ;
    }

    public function getRouteName () {
        return $this->route_name ;
    }

    public function setUrl ($url) {
        $this->url = $url ;
    }

    public function getUrl () {
        return $this->url ;
    }

    public function getDivider() {
        return $this->divider ;
    }
    
    public function setDivider( $divider ) {
         $this->divider = $divider ;
    }

    public function getIcon() {
        return $this->icon ;
    }
    
    public function setIcon( $icon ) {
         $this->icon = $icon ;
    }
    
    public function addChild(Menu $child){
        
        $child->parent  = $this ;
        
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
