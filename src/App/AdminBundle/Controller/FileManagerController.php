<?php

namespace App\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response; 
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Component\HttpFoundation\JsonResponse ;

use App\AdminBundle\Entity\File ;

/**
 * @author loong
 * @Route()
 */
class FileManagerController extends Controller {
    
    const TIME_OFFISET = 1218124800 ; //  mktime(0, 0, 0, 8, 8 , 2008 )
    
    /**
     * @Route("/filemanager/{admin_name}/{field_name}/{entity_id}", name="app_filemanager", requirements={"admin_name"="[\w\_]+", "field_name"="[\w\-]+", "entity_id"="\d+"})
     * @Template()
     */
    public function dialogAction(Request $request, $admin_name, $field_name, $entity_id )
    {
        $entity_id  = (int) $entity_id ;
        
        $loader = $this->container->get('app.admin.loader');
        
        if( !$loader->hasAdminName($admin_name) ) {
            return $this->onError( sprintf('admin `%s` not exists(%d)!', $admin_name, __LINE__ )) ;
        }
        
        $admin  = $loader->getAdminByName( $admin_name ) ;
        
        $config    = $admin->getDoctrineConfigBy('html', $field_name );
        if( !$config ) {
            return $this->onError( sprintf('admin `%s` property `%s` not exists(%d)!', $admin_name, $field_name, __LINE__ )) ; 
        }
        
        $option   = $admin->getFormOption( $field_name ) ;
        if( !$option ) {
            return $this->onError( sprintf('admin `%s` property `%s` not exists(%d)!', $admin_name, $field_name, __LINE__ )) ; 
        }
        
        $em = $this->container->get('doctrine')->getManager() ;
        $repo = $em->getRepository('App\AdminBundle\Entity\File') ;
        
        $session_id = $this->getRequest()->getSession()->getId() ;
        
        $file = new \App\AdminBundle\Entity\File() ;
        $form = $this->createFormBuilder($file, array(
                'label'        => 'Image' ,
            ))
            ->add('content', 'hidden', array(
                'label_render'        => false ,
            ))
            ->getForm() ;
        
        if( $request->isMethod('POST') ) {
             $action = $request->get('action', null ) ;
             
             if( 'delete' === $action ) {
                    $_file = $repo->loadByURL( $request->get('url') ) ;
                    if( !$_file ) {
                        return $this->onError( 'not exists!',  __LINE__ ) ; 
                    }
                    if( !$_file->getIsHtmlFile() ) {
                        return $this->onError( 'not exists!',  __LINE__ ) ; 
                    }
                    
                    if( $admin->getClassName() !== $_file->getClassName() ) {
                        return $this->onError( 'not exists!',  __LINE__ ) ; 
                    }
                    
                    if( $field_name !== $_file->getPropertyName() ) {
                        return $this->onError( 'not exists!',  __LINE__ ) ; 
                    }
                    
                    if( $entity_id !== $_file->getEntityId() ) {
                        return $this->onError( sprintf("entity id %s, %s not match", $entity_id, $_file->getEntityId() )  ,  __LINE__ ) ; 
                    }
                    
                    if( $_file->getSessionId() && $session_id !== $_file->getSessionId() ) {
                        return $this->onError( 'not exists!',  __LINE__ ) ; 
                    }
                    
                    $em->remove( $_file ) ;
                    $em->flush() ;
                    
                    return new JsonResponse(  array(
                        'removed' => true ,
                    ) ) ;
             }
             
             $form->bind($request);
             if ($form->isValid())  { 
                   /**
                    * @var \Symfony\Component\HttpFoundation\File\UploadedFile
                    */
                   $handel	= $request->files->get('attachment') ;

                   if( !$handel || !$handel->isValid() ) {
                       return $this->onError( 'upload invalid' ) ;
                   }

                   $ext	= strtolower( pathinfo( $handel->getClientOriginalName() , PATHINFO_EXTENSION) ) ;
                   if( !in_array($ext, $option['ext']) ) {
                       return $this->onError(  sprintf( 'only allow (%s) you upload %s', join(', ', $option['ext'] ), $ext  ) ) ;
                   }
                   if( $handel->getClientSize()  > $option['max'] ) {
                        return $this->onError(  sprintf( 'size %s bigger then %s ', $handel->getClientSize(), $option['max']  ) ) ;
                   }
                   
                   $file->setIsHtmlFile( true ) ;
                   $file->setSessionId( $session_id ) ;
                   $file->setName( $handel->getClientOriginalName()  ) ;
                   $file->setExt( $ext ) ;

                   $file->setSize( $handel->getClientSize() ) ;

                   $file->setType( $handel->getMimeType() ) ;

                   $file->setClassName( $admin->getClassName() ) ;
                   $file->setPropertyName( $field_name );
                   $file->setEntityId( $entity_id ) ;

                   $stream = fopen($handel->getPathname(), 'rb');
                   $data   = stream_get_contents( $stream ) ;
                   $file->setContent( $data ) ;

                   $_file = $repo->loadByURL( $request->get('content') ) ;
                   if( $_file 
                           && $admin->getClassName() === $_file->getClassName()
                           && $field_name === $_file->getPropertyName()
                           && $entity_id === $_file->getEntityId()
                           && $session_id === $_file->getSessionId()
                   ) {
                        $em->remove( $_file ) ;
                   }
                   
                   $em->persist( $file ) ;
                   $em->flush() ; 
                   
                   return  $this->sendJSON( array( 
                        'url'   => $file->__toString() , 
                        'name'   => $file->getName() , 
                        'ext'   => $ext , 
                        'size'   => $file->getSize() , 
                    ) );
             }
        }
        
        $query  = $repo->loadFilesForHtml( $admin->getClassName(), $field_name, $entity_id , $session_id ) ;
        
        return array(
            'default_value' => $request->get('value') ?: '' ,
            'list' => $query->getResult() ,
            'form' => $form->createView() ,
            'options' => $option ,
            'ext_list' => join('|', $option['ext'] ) ,
        );
    }
    
    /**
     * @Route("/upload/save/{admin_name}/{field_name}", name="app_upload_save")
     * @Template()
     */
    public function imageAction(Request $request, $admin_name, $field_name )
    {
        
        $entity_id     = (int) $request->get('id') ;
        
        $loader = $this->container->get('app.admin.loader');
        
        if( !$loader->hasAdminName($admin_name) ) {
            return $this->onError( sprintf('admin `%s` not exists(%d)!', $admin_name, __LINE__ )) ;
        }
        
        $admin  = $loader->getAdminByName( $admin_name ) ;
        
        $config    = $admin->getDoctrineConfigBy('file', $field_name );
        if( !$config ) {
            return $this->onError( sprintf('admin `%s` file `%s` not exists(%d)!', $admin_name, $field_name, __LINE__  )) ; 
        }
        
        $option   = $admin->getFormOption( $field_name ) ;
        if( !$option ) {
            return $this->onError( sprintf('admin `%s` file `%s` not exists(%d)!', $admin_name, $field_name, __LINE__  )) ; 
        }
        
        /**
    	 * @var \Symfony\Component\HttpFoundation\File\UploadedFile
    	 */
    	$handel	= $request->files->get('attachment') ;
    	
    	if( !$handel || !$handel->isValid() ) {
            return $this->onError( 'upload invalid' ) ;
    	}
        
    	$ext	= strtolower( pathinfo( $handel->getClientOriginalName() , PATHINFO_EXTENSION) ) ;
        if( !in_array($ext, $option['ext'])) {
            return $this->onError(  sprintf( 'only allow (%s) you upload %s', join(', ', $option['ext'] ), $ext  ) ) ;
        }
        
        $session_id = $this->getRequest()->getSession()->getId() ;
        
        $file   = new File() ;
        $file->setSessionId( $session_id ) ;
        $file->setName( $handel->getClientOriginalName()  ) ;
        $file->setExt( $ext ) ;
        
        $file->setSize( $handel->getClientSize() ) ;
        
        $file->setType( $handel->getMimeType() ) ;
        
        $file->setClassName( $admin->getClassName() ) ;
        $file->setPropertyName( $field_name );
        $file->setEntityId( $entity_id ) ;
        
        $stream = fopen($handel->getPathname(), 'rb');
        $data   = stream_get_contents( $stream ) ;
        $file->setContent( $data ) ;
        
        $em = $this->get('doctrine')->getManager() ;
        $_file = $em->getRepository('App\AdminBundle\Entity\File')->loadByURL( $request->get('url') ) ; 
        if( $_file 
                && $admin->getClassName() === $_file->getClassName()
                && $field_name === $_file->getPropertyName()
                && $entity_id === $_file->getEntityId()
                && $session_id === $_file->getSessionId() 
        ) {
            $em->remove( $_file ) ;
        }
        $em->persist( $file ) ;
        $em->flush() ; 
        
        return  $this->sendJSON( array( 
            'url'   => $file->__toString() , 
            'name'   => $file->getName() , 
            'ext'   => $ext , 
            'size'   => $file->getSize() , 
        ) );
    }
    
    /**
     * @Route("/upload/{type}/{uuid}.{ext}", name="app_upload_cache", requirements={"type" = "html|file", "uuid" = "[0-9a-f]{8}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{12}", "ext" = "\w{2,5}" } )
     * @Template()
     */
    public function fileViewAction(Request $request, $type, $uuid, $ext )
    {
        $repo   = $this->container->get('doctrine')->getManager()->getRepository('App\AdminBundle\Entity\File');
        $file   = $repo->loadByUUID($uuid) ;
        
        $is_debug   = $this->container->getParameter('kernel.debug') ;
        
        if( $file && $file->getSessionId() && $this->getRequest()->getSession()->getId()  !== $file->getSessionId() ) {
            if( $is_debug ) {
                \Dev::dump($file) ;
            }
            return new Response('Not Found', 404, array('Content-Type' => 'text/plain')) ;
        }
        
        if( !$file ) {
            return new Response('Not Found', 404, array('Content-Type' => 'text/plain')) ;
        }
        
        if( $ext !== $file->getExt() ) {
            if( $is_debug ) {
                \Dev::dump($file) ;
            }
            return new Response('Not Found', 404, array('Content-Type' => 'text/plain')) ;
        }
        
        $is_html_file = 'html' === $type ;
        
        if( $is_html_file !== $file->getIsHtmlFile () ) {
            if( $is_debug ) {
                \Dev::dump($file) ;
            }
            return new Response('Not Found', 404, array('Content-Type' => 'text/plain')) ;
        }
        
        $response = new Response();
        $response->setPrivate();
        
        $response->setMaxAge(600);
        $response->setSharedMaxAge(600);

        // set a custom Cache-Control directive
        $response->headers->addCacheControlDirective('must-revalidate', true);
         
        $date   =  $file->getUpdated() ;
        $response->setETag( $date->getTimestamp() ) ;
        $response->setLastModified($date) ;
        
        if ( $response->isNotModified($request) ) {
               return $response;
        }
        
        $response->headers->set('Content-Type', $file->getType() ) ;
        $response->setContent(  stream_get_contents( $file->getContent() ) ) ;
        
        return $response ;
    }
    
    /**
     * @return \Symfony\Component\HttpFoundation\Session\Session 
     */
    private function getSession(){
        return $this->getRequest()->getSession();
    }
    
    private function onError( $msg, $json = null ) {
        if(is_integer($json) ) {
            $json = array(
                'errno' => $json ,
            );
        } else if( !$json || !is_array($json) ) {
             $json   = array() ;
        }
        $json['error']  = $msg ;
        return new JsonResponse($json) ;
    }
    
    private function sendJSON(array $json ) {
        return new Response( json_encode($json) , 200, array(
                    'Content-Type'   => 'text/html' ,
                    'Cache-Control'  => 'max-age=0, private, no-store, no-cache' ,
                )) ;
    }
}