<?php

namespace App\AdminBundle\Compiler\MetaType\Admin ;

class Dashboard extends \App\AdminBundle\Compiler\MetaType\Type {
    
    public $position ;
    
    public $group ;
    
    public $label ;
    
    public $icon ;
    
    public function set_group( $group ) {
        if( preg_match('/\W/', $group ) ) {
            $this->throwPropertyError('group', "invalid value `%s`", $group ) ;
        }
        $this->group = $group ;
    }
    
    public function set_position( $position ) {
        if( preg_match('/\D/', $position ) ) {
            $this->throwPropertyError('position', "invalid value `%s`", $position ) ;
        }
        if( $position < 1 || $position > 0xff ) {
            $this->throwPropertyError('position', "invalid value `%s`", $position ) ;
        }
        $this->position= $position ;
    }
   
}
