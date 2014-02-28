<?php

namespace App\UserBundle\Admin ;

use Symfony\Bundle\FrameworkBundle\Controller\Controller ;
use Symfony\Component\HttpFoundation\Request ;
use App\AdminBundle\Compiler\Cache\ActionCache ;
use Symfony\Component\Form\Form ;

abstract class UserLogAdmin extends \App\AdminBundle\Compiler\Cache\AdminCache {
    
    public function initByRequest(\App\UserBundle\Entity\UserLog $log, \Symfony\Component\HttpFoundation\Request $request) {
        $log->ip    = $request->getClientIp() ;
        $log->browser   = $request->headers->get('user-agent') ;
    }
}