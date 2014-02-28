<?php

namespace App\AdminBundle\Compiler\Cache ;

trait AdminTree {
    
    /**
     * @var array
     */
    public $tree ;
    
    /**
     * @var array
     */
    protected $tree_object_id ;
    
    
    public function getTreeObject() {
        if( $this->tree_object_id ) {
             $object    =  $this->getObjectById( $this->tree_object_id ) ;
             if( !$object ) {
                 throw new \Exception(sprintf("tree node `%s` not exists",  $this->tree_object_id )) ;
             }
             if( $this->tree && isset($this->tree['leaf']) ) {
                if( $this->getReflectionProperty( $this->tree['leaf'] )->getValue( $object ) ) {
                    throw new \Exception(sprintf("tree parent object `%s` is leaf node", $this->string($object))) ;
                }
            }
            return $object ;
        }
    }
    
    public function getTreeObjectId() {
        return $this->tree_object_id;
    }
    
    public function setTreeObjectId( $id ) {
        $this->tree_object_id = $id ;
    }
     
}