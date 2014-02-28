<?php

namespace App\AdminBundle\Compiler\Loader ;

use Symfony\Component\DependencyInjection\ContainerInterface ;

use App\AdminBundle\Compiler\Cache\AdminCache ;

/**
 * Description of AdminLoader
 *
 * @author loong
 */
final class AdminLoader { 
    
    /** @var array */
    protected $admin_objects = array() ;
    
    /** @var array */
    private $_loader_cache = array() ;
    
    private $_admin_name_map ;
    
    private $app_domain ;
    
    /**
     * @var ContainerInterface 
     */
    private $container ;
    
    /**
     * @var string 
     */
    private $route_admin ;
    
    /**
     * @var string 
     */
    private $route_action ;
    
    /**
     * @var string 
     */
    private $cache_expired_time = 0 ;

    public function __construct(ContainerInterface $app, $cache_path , $expired_file ) {
        $this->container    = $app ;
        $this->app_domain   = $app->getParameter('app.admin.domain') ;
        $cache_expired = $this->isCacheExpired($cache_path, $expired_file) ; 
        
        $is_debug   = $app->getParameter('kernel.debug')  ;
        if( $cache_expired && 'cli' !== PHP_SAPI && !$is_debug ) {
            \Dev::dump($cache_expired);
            exit;
        }
        
        if( $cache_expired ) {
            $app->get('app.admin.generator') ;
        }
        
        $cache  = include( $cache_path ) ;
        foreach($cache as $i => $_cache ) {
            if( $i ) {
                foreach($_cache as $_key => $value ) {
                    $this->_loader_cache[ $_key ] = unserialize( $value ) ;
                }
            } else {
                foreach($_cache as $_key => $value ) {
                    $this->_loader_cache[ $_key ] = $value ;
                }
            }
        }
    }
    
    /**
     * @return ContainerInterface
     */
    public function getContainer(){
        return $this->container ;
    }
    
    public function getService($name) {
        return $this->container->get($name) ;
    }
    
    public function getCacheExpiredTime(){
        return $this->cache_expired_time;
    }
    
    private function isCacheExpired($cache_path, $path ){
        if( !file_exists($cache_path) ) {
            return __LINE__ ;
        }
        
        if( !file_exists($path) ) {
            return __LINE__ ;
        }
        
        $cache_time = filemtime($path);
        $this->cache_expired_time = $cache_time ;
        $dirs   = include($path) ;
      
        $cache_dir   = $this->container->getParameter('kernel.root_dir') . '/Resources/AppAdminBundle/src/' ;
        $root_dir    = dirname( $this->container->getParameter('kernel.root_dir') ) . '/' ;
        
        foreach($dirs as $dir => $entities ) {
            if( $dir ) {
                foreach($entities as $entity_path => $admin_path ) {
                    $_entity_path    = $root_dir . $dir . $entity_path ;
                    if( !file_exists($_entity_path) ) {
                        return __LINE__ ;
                    }
                    if( filemtime($_entity_path) > $cache_time ) {
                        return __LINE__ ;
                    }
                }
            } else {
                foreach($entities as $resource_path => $_file_expire_time ) {
                    $resource_full_path    = $root_dir . $resource_path ;
                    if( !file_exists($resource_full_path) ) {
                        return __LINE__ ;
                    }
                    if( filemtime($resource_full_path) > $cache_time ){
                        return __LINE__ ;
                    }
                }
            }
        }
        
        if( $this->container->getParameter('kernel.debug') ) { 
            foreach($dirs as $dir => $_files ) if( $dir ) {
                $finder     = new \Symfony\Component\Finder\Finder() ;
                $finder->name('*.php') ;
                foreach($finder->in( $root_dir . '/' . $dir ) as $file ) { 
                    $_file  = $file->getRelativePathname() ;
                    if( !isset($_files[$_file]) ) {
                        return __LINE__ ;
                    }
                }
            }
        }
        
        return 0 ;
    }
    
    public function getAppDomain() {
        return $this->app_domain ;
    }
    
    
    public function hasConfig($name) {
        return isset($this->_loader_cache[$name]);
    }
    
    public function getConfig($name) {
        if( !isset($this->_loader_cache[$name]) ) {
            throw new \Exception(sprintf("config %s is not exists", $name)) ;
        }
        return $this->_loader_cache[$name] ;
    }
    
    /** @return array */
    public function getEntityAlias(){
        return $this->_loader_cache['entity_alias'] ;
    }

    /** @return array */
    public function getAdminMaps(){
        return $this->_loader_cache['admin_maps'] ;
    }

    /** @return array */
    public function getAdminTree(){
        return $this->_loader_cache['admin_tree'] ;
    }

    /** @return array */
    public function getDashboard(){
        return $this->_loader_cache['dashboard'] ;
    }

    /** @return array */
    public function getMenu(){
        return $this->_loader_cache['menu']  ;
    }
    
    /** @return array */
    public function getDoctrineConfig(){
        return $this->_loader_cache['doctrine_config'] ;
    }
    
    /** @return array */
    public function getRoleHierarchy(){
        return $this->_loader_cache['role_hierarchy'] ;
    }
    
    /** @return mixed */
    public function getDoctrineConfigBy( $className , $type, $property ) {
        if( !isset($this->_loader_cache['doctrine_config'][$className][$type][$property]) ) {
            return null ;
        }
        return $this->_loader_cache['doctrine_config'][$className][$type][$property] ;
    }
    
    /**
     * 
     * @param string $class_name
     * @return AdminCache
     */
    public function getAdminByName( $admin_name ) {
        if( !isset( $this->_loader_cache['entity_alias'][ $admin_name] ) ) {
            throw new \Exception( sprintf("`%s` is not a admin object name", $admin_name) ) ;
        }
        return $this->getAdminByClass(  $this->_loader_cache['entity_alias'][ $admin_name ] ) ;
    }
    
    public function getNameByClass( $admin_class ) {
        if( null === $this->_admin_name_map ) {
            $this->_admin_name_map  = array_flip($this->_loader_cache['entity_alias']);
        }
        if( !isset( $this->_admin_name_map[ $admin_class ] ) ) {
            throw new \Exception( sprintf("`%s` is not a admin object", $admin_class) ) ;
        }
        return $this->_admin_name_map[ $admin_class ] ;
    }
    
    public function getClassByAdminName( $admin_name ) {
        if( !isset( $this->_loader_cache['entity_alias'][ $admin_name] ) ) {
            throw new \Exception( sprintf("`%s` is not a admin object name", $admin_name) ) ;
        }
        return $this->_loader_cache['entity_alias'][ $admin_name ] ;
    }
    
    public function hasAdminName( $admin_name ) {
        return isset( $this->_loader_cache['entity_alias'][ $admin_name ]) ;
    }
    
    public function hasAdminClass( $class_name ) {
        if( is_object($class_name) ) {
            $class_name = get_class($class_name) ;
        } else if ( !is_string($class_name) ) {
            throw new \Exception(sprintf("big error, expect string but get %s", gettype($class_name))) ;
        }
        if( !isset( $this->_loader_cache['admin_maps'][ $class_name ]) && is_subclass_of($class_name, 'Doctrine\ORM\Proxy\Proxy' ) ) {
            $class_name = get_parent_class( $class_name ) ;
        } 
        return isset( $this->_loader_cache['admin_maps'][ $class_name ]) ; 
    }
    
    public function hasSuperAdminClass( $super_class ) {
        return isset($this->_loader_cache['admin_unmaps'][ $super_class ]) ;
    }
    
    public function getSuperAdminClass( $super_class ) {
        if( !isset($this->_loader_cache['admin_unmaps'][ $super_class ]) ) {
            throw new \Exception(sprintf("admin_super_class(%s) not exists", $super_class)) ;
        }
        return $this->_loader_cache['admin_unmaps'][ $super_class ] ;
    }
    
    /**
     * 
     * @param string $class_name
     * @return AdminCache
     */
    public function getAdminByClass( $class_name ) {
        if( is_object($class_name) ) {
            $class_name = get_class($class_name) ;
        } else if ( !is_string($class_name) ) {
            throw new \Exception(sprintf("big error, expect string or object, but get %s", gettype($class_name))) ;
        }
        if( !isset( $this->_loader_cache['admin_maps'][ $class_name ]) && is_subclass_of($class_name, 'Doctrine\ORM\Proxy\Proxy' ) ) {
            $class_name = get_parent_class( $class_name ) ;
        }
        if( isset( $this->admin_objects[  $class_name ] ) ) {
            return $this->admin_objects[  $class_name ]  ;
        } 
        if( !isset( $this->_loader_cache['admin_maps'][ $class_name ]) ) {
            throw new \Exception( sprintf("`%s` is not a admin object class", $class_name) ) ;
        }
        $admin_class_name   = $this->_loader_cache['admin_maps'][ $class_name ] ;
        $admin  = new $admin_class_name( $this  ) ;
        $admin->setContainer( $this->container ) ;
        
        $this->admin_objects[ $class_name ] = $admin ;
        return $admin ;
    }

    public function generatePathByName1( $admin_name , $action_name , $options = array() ){
        $router = $this->container->get('router') ;
        $admin  = $this->getAdminByName( $admin_name ) ;
        $admin->setRouteParent(); 
        $action = $admin->getAction( $action_name ) ;
        return $action->path( $options ) ;
    }
    
    public function auth($admin_name, $action_name = null , $object = null ){
        return $this->getAdminByName($admin_name)->auth($action_name, $object ) ;
    }
    
    
    private $app_page_service ;
    
    public function appPathWithObject($name, $object, array $options = array() ){
        if( null === $this->app_page_service ) {
            $this->app_page_service = $this->container->get('app.page.service') ;
        }
        return $this->app_page_service->appPathWithObject($name, $object, $options ) ;
    }
    
    public function appPathWithoutObject($name, array $options = array() ){
        if( null === $this->app_page_service ) {
            $this->app_page_service = $this->container->get('app.page.service') ;
        }
        return $this->app_page_service->appPathWithoutObject($name,  $options ) ;
    }
    
    /**
     * @return \App\UserBundle\Entity\User
     */
    public function getCurrentLoginUser(){
        $securityContext = $this->container->get('security.context') ;
        $user   = $securityContext->getToken()->getUser() ;
        return $user ;
    }
    
    public function getCurrentLoginSecurityAuthorize( $admin_name = null ){
        $user   = $this->getCurrentLoginUser() ;
        $data   = null ;
        if( $user instanceof \App\UserBundle\Entity\User ) {
            $group  = $user->getUserGroup() ;
            if( $group ) {
                $data   = $group->getAuthorize() ;
                if( $admin_name ) {
                    $data = $data[$admin_name] ;
                }
            }
        }
        return $data ;
    }
    
    /**
     * @param string $admin
     * @param string $action
     */
    public function setRouteAdminAction($admin, $action){
        // \Dev::debug($admin, $action); exit;
        $this->route_admin  = $admin ;
        $this->route_action  = $action ;
    }
    
    /**
     * @return string
     */
    public function getRouteAdmin(){
        return $this->route_admin ;
    }
    
    /**
     * @return string
     */
    public function getRouteAction(){
        return $this->route_action ;
    }
    
    /**
     * @return \App\UserBundle\Entity\User
     */
    final public function getUser() {
        return $this->container->get('security.context')->getToken()->getUser();
    }
}
