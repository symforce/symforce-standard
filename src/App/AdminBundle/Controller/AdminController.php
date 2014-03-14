<?php

namespace App\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller; 

use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AdminController extends Controller 
{
    
    /**
     * @var \App\AdminBundle\Compiler\Cache\Loader
     */
    private $loader ;
    
    public function adminAction(Request $request)
    { 
        $this->loader   = $this->container->get('app.admin.loader') ;
        
        $cache  = $this->container->get('app.page.service') ;
//        $access = \Symfony\Component\PropertyAccess\PropertyAccess::createPropertyAccessor() ;
        
        $option = $cache->getAdminOption( $request->attributes->get('_app_route_name') ) ;
        
        $action = $option['dispatcher']($this->loader, $request) ;
        
        if( !$this->loader->auth( $action->getAdmin()->getName(), $action->getName()) ) {
            throw new AccessDeniedException();
        }
        return $action->onController( $this, $request );
    }
    
}
