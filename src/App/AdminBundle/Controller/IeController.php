<?php

namespace App\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response; 
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use App\AdminBundle\Entity\File ;

/**
 * @Route("/ie")
 */
class IeController extends Controller {
    
    const EXPIRED_TIME  =  604800 ; // 24*3600*7
    
    /**
     * @Route("/{name}.htc", requirements={"name"="[\w\_\-]+"} )
     */
    public function htcAction(Request $request, $name )
    {
        return $this->getResponse($request, $name, array(
            'pie'   => __DIR__ . '/../Resources/public/ie/css3pie/PIE.htc'  ,
            'box'   => __DIR__ . '/../Resources/public/ie/boxsizing.htc'  ,
        ), 'text/x-component' );
    }
    
    /**
     * @Route("/{name}.js", requirements={"name"="[\w\_\-]+"} )
     */
    public function jsAction(Request $request, $name )
    {
        return $this->getResponse($request, $name, array(
            'PIE_IE678'   => __DIR__ . '/../Resources/public/ie/css3pie/PIE_IE678.js'  ,
            'PIE_IE9'   => __DIR__ . '/../Resources/public/ie/css3pie/PIE_IE9.js'  ,
        ), 'text/javascript');
        
    }
    
    private function getResponse(Request $request, $name, array $files, $content_type ){
        
        if( !isset( $files[$name] ) ) {
            return new Response('Not Found', 404, array('Content-Type' => 'text/plain')) ;
        }
        $file = $files[ $name ] ;
        
        $response = new Response();
        $response->setPublic();
        
        $response->setMaxAge( self::EXPIRED_TIME );
        $response->setSharedMaxAge( self::EXPIRED_TIME  );
        $response->headers->set('Content-Type', $content_type ) ;

        // set a custom Cache-Control directive
        $response->headers->addCacheControlDirective('must-revalidate', true);
         
        $date   = new \DateTime() ;
        $date->setTimestamp( strtotime( $this->container->getParameter('app.version') ) ) ;
        $response->setETag( $date->getTimestamp() ) ;
        $response->setLastModified($date) ;
        
        if ( $response->isNotModified($request) ) {
               return $response;
        }
        
        if( !file_exists($file) ) {
            throw new \Exception(sprintf("file `%s`, `%s` not exists", $name, $file)) ;
        }
        
        $response->setContent(file_get_contents( $file ) ) ;
        return $response ;
    }
}
