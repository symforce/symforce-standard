<?php

namespace App\AdminBundle\Compiler\MetaType\Action ;

class CustomizeAction extends AbstractAction {
    
    const USED_NAME  = ' root tree id property owner page entity admin action' ;
    
    public function set_name($name) {
        if( preg_match('/\s'  . preg_quote($name) . '\s/',  self::USED_NAME ) ) {
            $this->throwError("action.name can not set to `%s`", $name);
        }
        if( preg_match('/\W/', $name ) ) {
            $this->throwPropertyError('name', "invalid value `%s`", $name ) ;
        }
        $this->name = $name ;
    }
    
    protected function set_class( $class_name ) {
        if( !class_exists($class_name) ) {
            $this->throwError( " %s not exists!", $class_name ) ;
        }
        $rc = new \ReflectionClass( $class_name ) ;
        $parent_name = 'App\AdminBundle\Compiler\Cache\CustomizeActionCache' ;
        if( !$rc->isSubclassOf( $parent_name ) ) {
            $this->throwError( " %s should extend form %s", $class_name, $parent_name ) ;
        }
        $this->parent_class_name   = $class_name ; 
    }

    public function isCustomize(){
        return true ;
    }
}
