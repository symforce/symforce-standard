<?php

namespace App\AdminBundle\Compiler\Cache ;

use Symfony\Component\DependencyInjection\ContainerInterface ;

class BatchActionCache extends ActionCache {
        
    public function isRequestObject() {
        return false ;
    }
    
    public function isBatchAction() {
        return true ;
    }
    
}

