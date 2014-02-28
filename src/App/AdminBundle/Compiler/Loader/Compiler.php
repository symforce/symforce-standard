<?php

namespace App\AdminBundle\Compiler\Loader ;

class Compiler {
    
    const STAT_OK = 0 ;
    const STAT_ADMIN = 1 ;
    const STAT_ROUTE = 2 ;
    const STAT_PASS = 0xf ;
    
    private $status = 0 ;
    
    public function isOk(){
        return self::STAT_OK === $this->status ;
    }
    
    public function set( $value ) {
        if( $value ) {
            if( $this->status ) {
                throw new \Exception(sprintf("error: compile status from %d to %d, refresh may fix this problem", $value, $this->status));
            }
        } else {
            if( !$this->status ) {
                throw new \Exception(sprintf("error: compile status from %d to %d, refresh may fix this problem", $value, $this->status));
            }
        }
        $this->status = $value ;
    }
}