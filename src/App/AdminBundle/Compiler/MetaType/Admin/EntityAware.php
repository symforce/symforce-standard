<?php

namespace App\AdminBundle\Compiler\MetaType\Admin ;

/**
 * Description of EntityAware
 *
 * @author loong
 */
abstract class EntityAware extends \App\AdminBundle\Compiler\MetaType\Type {
    
    /**
     * @var \App\AdminBundle\Compiler\MetaType\Admin\Entity
     */
    public $admin_object ;
    
    /**
     * @var \App\AdminBundle\Compiler\Generator\TransGeneratorValue 
     */
    public $label ;

    /**
     * @var \App\AdminBundle\Compiler\Generator\TransGeneratorNode 
     */
    public $tr_node ;
    
    /**
     * @var \App\AdminBundle\Compiler\Generator\PhpClass 
     */
    protected $_compile_class ;
    
    public function setAdminObject(Entity $entity ) {
        $this->admin_object     = $entity ;
    }
    
    private $_chain_exception   = null ;
    public function setChainException(\Exception $e){
        $this->_chain_exception = $e ;
    }
    
    public function throwError() {

        $msg   = call_user_func_array('sprintf', func_get_args() ) ;

        $_msg= sprintf("%s, from annotation: `%s`, class: `%s` file: `%s` ",
                $msg ,
                $this->getMeteTypeName(), $this->admin_object->class_name, $this->admin_object->getFileName() )  ; 

        if( $this->_chain_exception ) {
            throw new \Exception( $_msg, __LINE__ , $this->_chain_exception );
        } else {
            throw new \Exception( $_msg );
        }
    }
    
}
