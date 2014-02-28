<?php

namespace App\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class PageController extends Controller
{
    
    /**
     * @var AdminLoader
     */
    protected $loader ;
    
    /**
     * @var \App\AdminBundle\Compiler\Cache\AdminCache 
     */
    protected $page_admin ;
    
    protected $page_object ;
    protected $page_objects = array() ;
    
    /**
     * @var \Symfony\Component\HttpFoundation\Response
     */
    protected $response ;
    
    final public function getAdminByClass($class) {
        return $this->loader->getAdminByClass( $class ) ;
    }

    public function setPageAdmin($admin_name) {
        $this->page_admin   = $this->loader->getAdminByName( $admin_name ) ;
    }
    
    public function getPageObject($admin_name, $id ) {
        $admin  = $this->loader->getAdminByName( $admin_name ) ;
        
        $property_id = $admin->getPropertyIdName() ;
        $property_slug = $admin->getPropertySlugName() ;
        $repo     = $admin->getRepository() ;
        if( empty($property_slug) ) {
            $object = $repo->findOneBy(array(
                 $property_id   => $id ,
            ));
        } else {
            $object = $repo->findOneBy(array(
                 $property_slug => $id ,
            ));
            if( !$object && preg_match('/^\d+$/', $id) ) {
               $object = $repo->findOneBy(array(
                    $property_id  => $id ,
               ));
            }
        }
        if( !$object ) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
        }
        $this->page_object  = $object ;
        return $object ;
    }
    
    public function setPageObject($object, $admin_name, $property_value ){
        $this->page_objects[ $admin_name ] = $object ;
        $admin  = $this->loader->getAdminByName( $admin_name ) ;
        
        $property_id = $admin->getPropertyIdName() ;
        $value  = (string) $admin->getReflectionProperty( $property_id )->getValue( $object ) ;
        if( $value === $property_value ) {
            return ;
        }
        $property_slug = $admin->getPropertySlugName() ;
        if( !empty($property_slug) ) {
            $value = (string) $admin->getReflectionProperty( $property_slug )->getValue( $object ) ;
            if( $value === $property_value ) {
                return ;
            }
        }
        throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }
    
    final public function dispatchAction(Request $request){
        $this->loader   = $this->container->get('app.admin.loader') ;
        
        $cache  = $this->container->get('app.page.service') ;
        $access = \Symfony\Component\PropertyAccess\PropertyAccess::createPropertyAccessor() ;
        
        $page_action    = $request->attributes->get('_app_web_page') ;
        $option = $cache->getOption( $page_action ) ;
        $response   = $option['dispatcher']($access, $this, $request);
        if( $response instanceof \Symfony\Component\HttpFoundation\Response ) {
            return $response ;
        } else {
            if( !isset($response['page'] ) ) {
                $response['page']   = array('name' => $page_action ) ;
            }
            return $this->render( $option['template'], $response, $this->response );
        }
    }
    
    
    /**
     * @return \App\UserBundle\Entity\User
     */
    final public function getUser() {
        return $this->container->get('security.context')->getToken()->getUser();
    }

    final public function defaultIndexAction(){
        return array(
            'admin' => $this->page_admin , 
        );
    }
    
    final public function defaultViewAction(){
        return array(
            'page_object'   => $this->page_object ,
        );
    }
}
